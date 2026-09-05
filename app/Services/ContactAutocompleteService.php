<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\ContactAutocompleteCache;
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
     * Search for contacts matching the query.
     *
     * @param int $userId
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function search(int $userId, string $query, int $limit = 10): Collection
    {
        return ContactAutocompleteCache::where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->where(function ($q) use ($query) {
                $q->where('email', 'LIKE', "%{$query}%")
                  ->orWhere('name', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('frequency')
            ->limit($limit)
            ->get(['email', 'name', 'frequency']);
    }

    /**
     * Get recent recipients sorted by frequency.
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
}