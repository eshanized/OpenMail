<?php

namespace App\Services;

use App\Models\ThreadHeaderCache;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ThreadBuilder
{
    private const MAX_DEPTH = 50;

    /**
     * Build conversation threads from a collection of messages using JWZ algorithm.
     *
     * @param  Collection<int, object>  $messages  Messages with message_id, in_reply_to, references, subject, date, etc.
     * @return array<int, object> Root threads with nested children
     */
    public function buildThreads(Collection $messages): array
    {
        if ($messages->isEmpty()) {
            return [];
        }

        // 1. Index messages by Message-ID (cleaned)
        $byMessageId = $messages
            ->filter(fn ($m) => ! empty($m->message_id))
            ->keyBy(fn ($m) => $this->cleanMessageId($m->message_id));

        // 2. Build parent-child relationships (first pass - allow all)
        $children = [];
        $parentOf = [];

        foreach ($byMessageId as $messageId => $message) {
            $parentId = $this->extractParentId($message);

            if ($parentId && isset($byMessageId[$parentId])) {
                $children[$parentId][] = $message;
                $parentOf[$messageId] = $parentId;
            }
        }

        // 3. Detect and break cycles (JWZ step: prune empty containers / handle cycles)
        // Find all nodes that are part of cycles
        $inCycle = $this->findCycles($byMessageId, $parentOf);

        // Break cycles by removing the parent link for nodes in cycles
        foreach (array_keys($inCycle) as $messageId) {
            $parentId = $parentOf[$messageId];
            if ($parentId && isset($children[$parentId])) {
                $children[$parentId] = array_values(array_filter(
                    $children[$parentId],
                    fn ($m) => $this->cleanMessageId($m->message_id) !== $messageId
                ));
                if (empty($children[$parentId])) {
                    unset($children[$parentId]);
                }
            }
            unset($parentOf[$messageId]);
        }

        // 4. Identify roots (messages without parents)
        $roots = [];
        foreach ($byMessageId as $messageId => $message) {
            if (! isset($parentOf[$messageId])) {
                $roots[] = $message;
            }
        }

        // 5. Prune empty containers (already handled - only real messages in tree)

        // 6. Subject-based grouping for orphaned roots (D-02 fallback)
        $roots = $this->groupBySubject($roots);

        // 7. Sort threads by latest message date descending
        usort($roots, function ($a, $b) {
            $dateA = $this->getLatestDate($a)?->timestamp ?? 0;
            $dateB = $this->getLatestDate($b)?->timestamp ?? 0;

            return $dateB <=> $dateA;
        });

        // 8. Attach children recursively and compute metadata
        return $this->attachChildren($roots, $children);
    }

    /**
     * Find all message IDs that are part of cycles in the parent-child graph.
     * Uses DFS with three-color marking (white=unvisited, gray=visiting, black=visited).
     */
    private function findCycles(Collection $byMessageId, array $parentOf): array
    {
        $color = []; // 0=white, 1=gray, 2=black
        $inCycle = [];

        foreach ($byMessageId as $messageId => $message) {
            if (! isset($color[$messageId])) {
                $this->dfsDetectCycle($messageId, $parentOf, $color, $inCycle);
            }
        }

        return $inCycle;
    }

    private function dfsDetectCycle(string $nodeId, array $parentOf, array &$color, array &$inCycle): void
    {
        $color[$nodeId] = 1; // gray - visiting

        if (isset($parentOf[$nodeId])) {
            $parentId = $parentOf[$nodeId];

            if (! isset($color[$parentId])) {
                $this->dfsDetectCycle($parentId, $parentOf, $color, $inCycle);
            } elseif ($color[$parentId] === 1) {
                // Found a back edge - cycle detected
                // Mark all nodes in the cycle
                $cycleNode = $nodeId;
                $inCycle[$cycleNode] = true;
                while ($cycleNode !== $parentId) {
                    $cycleNode = $parentOf[$cycleNode];
                    $inCycle[$cycleNode] = true;
                }
            }
        }

        $color[$nodeId] = 2; // black - visited
    }

    /**
     * Extract parent Message-ID from In-Reply-To or References header.
     * Prefers In-Reply-To, falls back to last References entry.
     */
    public function extractParentId(object $message): ?string
    {
        if (! empty($message->in_reply_to)) {
            return $this->cleanMessageId($message->in_reply_to);
        }

        if (! empty($message->references)) {
            $refs = array_filter(array_map('trim', explode(' ', $message->references)));
            if (! empty($refs)) {
                return $this->cleanMessageId(end($refs));
            }
        }

        return null;
    }

    /**
     * Clean Message-ID by stripping angle brackets and whitespace.
     */
    public function cleanMessageId(string $id): string
    {
        return trim($id, '<> ');
    }

    /**
     * Group rootless messages by normalized subject + 2-day window (D-02).
     * Strips "Re:", "Fwd:" (case-insensitive), trims whitespace.
     * Messages with same normalized subject within 2 days are grouped together.
     */
    public function groupBySubject(Collection|array $messages): array
    {
        if (empty($messages)) {
            return [];
        }

        $messages = collect($messages);

        // First, normalize subjects
        $normalized = $messages->map(function ($m) {
            $subject = trim($m->subject ?? ''); // Trim first to handle leading/trailing whitespace
            $subject = preg_replace('/^(Re|Fwd):\s*/i', '', $subject);
            $subject = trim(preg_replace('/\s+/', ' ', $subject));
            $m->normalized_subject = strtolower($subject);

            return $m;
        });

        // Group by normalized subject first
        $bySubject = $normalized->groupBy('normalized_subject');

        $result = [];

        foreach ($bySubject as $subject => $group) {
            // Within each subject group, cluster by 2-day windows
            $sorted = $group->sortBy('date');
            $clusters = [];
            $currentCluster = [];

            foreach ($sorted as $message) {
                if (empty($currentCluster)) {
                    $currentCluster[] = $message;
                } else {
                    $firstDate = $currentCluster[0]->date instanceof Carbon
                        ? $currentCluster[0]->date
                        : Carbon::parse($currentCluster[0]->date);
                    $messageDate = $message->date instanceof Carbon
                        ? $message->date
                        : Carbon::parse($message->date);

                    // Check if within 2 days (48 hours) of first message in cluster
                    if ($firstDate->diffInHours($messageDate, true) <= 48) {
                        $currentCluster[] = $message;
                    } else {
                        $clusters[] = $currentCluster;
                        $currentCluster = [$message];
                    }
                }
            }

            if (! empty($currentCluster)) {
                $clusters[] = $currentCluster;
            }

            // Pick latest message from each cluster as representative
            foreach ($clusters as $cluster) {
                $latest = collect($cluster)->sortByDesc('date')->first();
                $result[] = $latest;
            }
        }

        return $result;
    }

    /**
     * Recursively attach children to parent nodes.
     */
    private function attachChildren(array $roots, array $children): array
    {
        foreach ($roots as &$root) {
            $root->children = $children[$root->message_id] ?? [];
            $root->children = $this->attachChildren($root->children, $children);

            // Compute derived properties for UI
            $root->latestDate = $this->getLatestDate($root);
            $root->unreadCount = $this->countUnread($root);
            $root->from_display = $this->getFromDisplay($root);
            $root->formatted_date = $this->formatDate($root->latestDate);
            $root->has_attachments = $this->hasAttachmentsInThread($root);
            $root->labels = ($root->labels ?? null) instanceof Collection
                ? $root->labels
                : collect();
        }

        return $roots;
    }

    /**
     * Get the latest date in a thread (including children).
     */
    private function getLatestDate(object $thread): ?Carbon
    {
        $raw = $thread->date ?? null;
        $latest = null;

        if ($raw instanceof Carbon) {
            $latest = $raw;
        } elseif ($raw instanceof \DateTimeInterface) {
            $latest = Carbon::instance($raw);
        } elseif (is_string($raw) && trim($raw) !== '') {
            try {
                $latest = Carbon::parse($raw);
            } catch (\Throwable) {
                $latest = null;
            }
        }

        foreach (($thread->children ?? []) as $child) {
            $childLatest = $this->getLatestDate($child);
            if ($childLatest && (! $latest || $childLatest->gt($latest))) {
                $latest = $childLatest;
            }
        }

        return $latest;
    }

    /**
     * Count unread messages in a thread (including children).
     */
    private function countUnread(object $thread): int
    {
        $count = ($thread->is_seen ?? false) ? 0 : 1;

        foreach (($thread->children ?? []) as $child) {
            $count += $this->countUnread($child);
        }

        return $count;
    }

    /**
     * Get display name for thread (from latest message).
     */
    private function getFromDisplay(object $thread): string
    {
        // Find the message with the latest date
        $messages = $this->collectThreadMessages($thread);
        $latestMessage = $messages->sortByDesc(function ($m) {
            return $this->getLatestDate($m)?->timestamp ?? 0;
        })->first() ?? $thread;

        $name = trim($latestMessage->from_name ?? '');
        $display = trim($latestMessage->from_display ?? '');
        $address = trim($latestMessage->from_address ?? '');

        if (! empty($name)) {
            return $name;
        }
        if (! empty($display)) {
            return $display;
        }
        if (! empty($address)) {
            return $address;
        }

        return 'Unknown';
    }

    /**
     * Format date for display.
     */
    private function formatDate(?Carbon $date): string
    {
        if (! $date) {
            return '';
        }

        $now = now();
        if ($date->isToday()) {
            return $date->format('g:i A');
        } elseif ($date->isYesterday()) {
            return 'Yesterday';
        } elseif ($date->year === $now->year) {
            return $date->format('M j');
        } else {
            return $date->format('M j, Y');
        }
    }

    /**
     * Check if any message in thread has attachments.
     */
    private function hasAttachmentsInThread(object $thread): bool
    {
        if (! empty($thread->has_attachments)) {
            return true;
        }

        foreach (($thread->children ?? []) as $child) {
            if ($this->hasAttachmentsInThread($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Collect all messages in a thread (root + children) recursively.
     */
    private function collectThreadMessages(object $thread): Collection
    {
        $messages = collect([$thread]);

        foreach (($thread->children ?? []) as $child) {
            $messages = $messages->merge($this->collectThreadMessages($child));
        }

        return $messages;
    }

    /**
     * Resolve missing parent Message-IDs from thread_header_cache.
     *
     * @param  array<string>  $missingMessageIds  Message-IDs not found in current message set
     * @param  int  $userId  Current user ID
     * @return Collection<int, object> Cached header data for found Message-IDs
     */
    public function resolveMissingParentsFromCache(array $missingMessageIds, int $userId): Collection
    {
        if (empty($missingMessageIds)) {
            return collect();
        }

        // Query thread_header_cache for non-expired entries
        $cached = ThreadHeaderCache::where('user_id', $userId)
            ->whereIn('message_id', $missingMessageIds)
            ->where('expires_at', '>', now())
            ->get();

        return $cached->map(function ($cache) {
            return (object) [
                'message_id' => $cache->message_id,
                'in_reply_to' => $cache->in_reply_to,
                'references' => $cache->references,
                'subject' => $cache->subject,
                'date' => $cache->date,
                'folder_path' => $cache->folder_path,
                'uid' => $cache->uid,
                'from_address' => '',
                'from_name' => '',
                'to_address' => '',
                'is_seen' => false,
                'is_flagged' => false,
                'has_attachments' => false,
                'snippet' => '',
                'labels' => collect(),
            ];
        });
    }
}
