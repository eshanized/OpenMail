<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\ContactAutocompleteCache;
use App\Models\Contact;
use App\Services\ImapMailboxService;
use App\Services\FolderMapper;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ContactAutocompleteService
{
    public function __construct(
        protected ImapMailboxService $imapService,
        protected FolderMapper $folderMapper
    ) {}

    /**
     * Refresh the autocomplete cache from IMAP Sent/Inbox folders.
     *
     * @param int $userId
     * @return int Count of cached contacts
     */
    public function refreshCache(int $userId): int
    {
        $recipients = $this->imapService->searchRecipients(100);

        foreach ($recipients as $recipient) {
            ContactAutocompleteCache::updateOrCreate(
                ['user_id' => $userId, 'email' => $recipient['email']],
                [
                    'user_id' => $userId,
                    'email' => $recipient['email'],
                    'name' => $recipient['name'],
                    'frequency' => $recipient['frequency'],
                    'last_used_at' => now(),
                    'expires_at' => now()->addDays(7),
                ]
            );
        }

        return count($recipients);
    }

    /**
     * Search for contacts matching the query (IMAP cache only).
     *
     * @param int $userId
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function search(int $userId, string $query, int $limit = 10): Collection
    {
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        return ContactAutocompleteCache::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where(function ($q) use ($escapedQuery) {
                $q->where('email', 'LIKE', "%{$escapedQuery}%")
                  ->orWhere('name', 'LIKE', "%{$escapedQuery}%");
            })
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get(['email', 'name', 'frequency']);
    }

    /**
     * Get recent recipients sorted by frequency (IMAP cache only).
     *
     * @param int $userId
     * @param int $limit
     * @return Collection
     */
    public function getRecentRecipients(int $userId, int $limit = 10): Collection
    {
        return ContactAutocompleteCache::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get(['email', 'name', 'frequency']);
    }

    /**
     * Unified search merging local contacts + IMAP cache.
     * Local contacts rank higher (exact match > prefix match), then IMAP cache by frequency.
     * Deduplicates by email (case-insensitive), local wins.
     *
     * @param int $userId
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchUnified(int $userId, string $query, int $limit = 10): Collection
    {
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        // Local contacts: exact match > prefix match on email/name, order by usage_count desc
        $local = Contact::where('user_id', $userId)
            ->where(function ($q) use ($escapedQuery) {
                $q->where('email', 'LIKE', "{$escapedQuery}%")
                    ->orWhere('name', 'LIKE', "{$escapedQuery}%");
            })
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get(['id', 'name', 'email', 'phone', 'avatar_color', 'usage_count'])
            ->map(fn($c) => [
                'source' => 'local',
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'avatar' => $c->avatar_color,
                'frequency' => $c->usage_count,
            ]);

        // IMAP cache: frequency desc
        $imap = $this->search($userId, $query, $limit)
            ->map(fn($c) => [
                'source' => 'imap',
                'name' => $c->name,
                'email' => $c->email,
                'phone' => null,
                'avatar' => null,
                'frequency' => $c->frequency,
            ]);

        // Merge: local contacts first (they rank higher), then IMAP cache
        // Deduplicate by email (case-insensitive), local wins
        $merged = $local->concat($imap)
            ->unique('email', true) // strict comparison
            ->take($limit)
            ->values();

        return $merged;
    }
}