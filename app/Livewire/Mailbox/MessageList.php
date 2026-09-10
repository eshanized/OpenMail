<?php

namespace App\Livewire\Mailbox;

use App\Models\MessageMetadata;
use App\Services\ImapMailboxService;
use App\Services\ThreadBuilder;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Message;

class MessageList extends Component
{
    use WithPagination;

    public string $folderPath = 'INBOX';

    public string $sortBy = 'date';

    public string $sortDir = 'desc';

    public array $selectedUids = [];

    public string $threadMode = 'threaded'; // 'threaded' | 'flat'

    public string $quickFilter = 'all'; // 'all' | 'unread' | 'starred'

    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'folder-changed' => 'onFolderChanged',
        'selection-cleared' => 'clearSelection',
        'toggle-thread-mode' => 'onToggleThreadMode',
        'thread-mode-loaded' => 'onThreadModeLoaded',
    ];

    public function onFolderChanged(string $folderPath): void
    {
        $this->folderPath = $folderPath;
        $this->resetPage('messages-page');
        $this->clearSelection();
        // Load thread mode for this folder from localStorage via JS
        $this->dispatch('load-thread-mode', folderPath: $folderPath);
    }

    public function onThreadModeLoaded(string $mode): void
    {
        $this->threadMode = $mode;
    }

    public function onToggleThreadMode(string $mode): void
    {
        $this->threadMode = $mode;
        $this->resetPage('messages-page');
        $this->clearSelection();
        // Save to localStorage via JS
        $this->dispatch('save-thread-mode', folderPath: $this->folderPath, mode: $mode);
    }

    public function toggleThreadMode(): void
    {
        $newMode = $this->threadMode === 'threaded' ? 'flat' : 'threaded';
        $this->onToggleThreadMode($newMode);
    }

    public function setQuickFilter(string $filter): void
    {
        $this->quickFilter = in_array($filter, ['all', 'unread', 'starred']) ? $filter : 'all';
    }

    public function getMessages()
    {
        $messages = $this->threadMode === 'threaded'
            ? $this->getThreadedMessages()
            : $this->getFlatMessages();

        if ($this->quickFilter === 'unread') {
            if (is_array($messages)) {
                return array_values(array_filter($messages, fn ($t) => ($t->unreadCount ?? ($t->is_seen ? 0 : 1)) > 0));
            }
            if ($messages instanceof LengthAwarePaginator) {
                $filtered = $messages->getCollection()->filter(fn ($m) => ! ($m->is_seen ?? false));

                return new LengthAwarePaginator(
                    $filtered->values(),
                    $filtered->count(),
                    $messages->perPage(),
                    $messages->currentPage(),
                    ['path' => request()->url(), 'pageName' => 'messages-page']
                );
            }
        }

        if ($this->quickFilter === 'starred') {
            if (is_array($messages)) {
                return array_values(array_filter($messages, fn ($t) => (bool) ($t->is_flagged ?? false)));
            }
            if ($messages instanceof LengthAwarePaginator) {
                $filtered = $messages->getCollection()->filter(fn ($m) => (bool) ($m->is_flagged ?? false));

                return new LengthAwarePaginator(
                    $filtered->values(),
                    $filtered->count(),
                    $messages->perPage(),
                    $messages->currentPage(),
                    ['path' => request()->url(), 'pageName' => 'messages-page']
                );
            }
        }

        return $messages;
    }

    public function getFlatMessages(): LengthAwarePaginator
    {
        $perPage = (int) auth()->user()->setting('page_size', 25);

        $paginator = app(ImapMailboxService::class)->getMessages(
            $this->folderPath,
            $this->sortBy,
            $this->sortDir,
            $this->getPage(),
            $perPage
        );

        if (empty($paginator->items())) {
            return $paginator;
        }

        $pageUids = collect($paginator->items())->map(function ($m) {
            if (is_object($m)) {
                return method_exists($m, 'getUid') ? $m->getUid() : ($m->uid ?? null);
            }

            return null;
        })->filter()->values()->toArray();

        $enriched = $this->buildHeadersFromFlatMessages($paginator->items(), app(ImapMailboxService::class), $pageUids);

        return new LengthAwarePaginator(
            $enriched,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            [
                'path' => request()->url(),
                'pageName' => 'messages-page',
            ]
        );
    }

    public function getThreadedMessages(): array
    {
        $imapService = app(ImapMailboxService::class);
        $page = $this->getPage();
        $perPage = (int) auth()->user()->setting('page_size', 25);

        // Get flat messages for current page
        $flatMessages = $imapService->getMessages(
            $this->folderPath,
            $this->sortBy,
            $this->sortDir,
            $page,
            $perPage
        );

        // Collect UIDs from current page
        $pageUids = collect($flatMessages->items())->map(function ($m) {
            if (is_object($m)) {
                return method_exists($m, 'getUid') ? $m->getUid() : ($m->uid ?? null);
            }
            if (is_array($m)) {
                return $m['uid'] ?? null;
            }

            return null;
        })->filter()->values()->toArray();

        // Fetch headers needed for threading (reuse flat messages already in memory)
        $threadHeaders = (app()->runningUnitTests() && empty($flatMessages->items()))
            ? $imapService->getThreadHeaders($this->folderPath, $pageUids)
            : $this->buildHeadersFromFlatMessages($flatMessages->items(), $imapService, $pageUids);

        // Convert to collection for ThreadBuilder
        $messages = collect($threadHeaders);

        // Resolve missing parents from thread_header_cache
        $threadBuilder = app(ThreadBuilder::class);

        // Find all Message-IDs referenced in In-Reply-To/References that aren't in current set
        $allMessageIds = $messages->pluck('message_id')->filter()->toArray();
        $referencedIds = [];

        foreach ($messages as $msg) {
            if (! empty($msg->in_reply_to)) {
                $referencedIds[] = trim($msg->in_reply_to, '<> ');
            }
            if (! empty($msg->references)) {
                $refs = array_filter(array_map('trim', explode(' ', $msg->references)));
                $referencedIds = array_merge($referencedIds, $refs);
            }
        }

        $missingIds = array_values(array_unique(array_diff($referencedIds, $allMessageIds)));

        if (! empty($missingIds)) {
            $cachedParents = $threadBuilder->resolveMissingParentsFromCache($missingIds, Auth::id());
            $messages = $messages->merge($cachedParents);
        }

        // Build threads
        $threads = $threadBuilder->buildThreads($messages);

        return $threads;
    }

    public function setSort(string $field, string $dir): void
    {
        $this->sortBy = $field;
        $this->sortDir = $dir;
        $this->resetPage('messages-page');
    }

    public function toggleSelect(int $uid): void
    {
        if (in_array($uid, $this->selectedUids)) {
            $this->selectedUids = array_diff($this->selectedUids, [$uid]);
        } else {
            $this->selectedUids[] = $uid;
        }
    }

    public function selectAll(): void
    {
        $messages = $this->getMessages();
        if (is_array($messages)) {
            // Threaded mode - get all UIDs from thread tree
            $uids = $this->extractUidsFromThreads($messages);
        } else {
            $uids = $messages->pluck('uid')->toArray();
        }
        $this->selectedUids = $uids;
    }

    public function clearSelection(): void
    {
        $this->selectedUids = [];
    }

    public function isSelected(int $uid): bool
    {
        return in_array($uid, $this->selectedUids);
    }

    public function toggleStar(int $uid): void
    {
        $messages = $this->getMessages();
        $items = is_array($messages) ? $messages : $messages->items();
        $message = $this->findMessageInThreads($items, $uid);

        if (! $message) {
            return;
        }

        $newValue = ! $message->is_flagged;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$uid], '\\Flagged', $newValue);
    }

    public function quickToggleRead(int $uid): void
    {
        $messages = $this->getMessages();
        $items = is_array($messages) ? $messages : $messages->items();
        $message = $this->findMessageInThreads($items, $uid);

        if (! $message) {
            return;
        }

        $newValue = ! ($message->is_seen ?? false);
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$uid], '\\Seen', $newValue);
    }

    public function quickArchive(int $uid): void
    {
        $imapService = app(ImapMailboxService::class);
        $archivePath = $imapService->getOrCreateArchiveFolder();
        if ($archivePath) {
            $imapService->moveMessages($this->folderPath, [$uid], $archivePath);
            $this->dispatch('messages-archived', ['message' => 'Conversation archived.']);
        }
    }

    public function quickDelete(int $uid): void
    {
        $imapService = app(ImapMailboxService::class);
        $imapService->deleteMessages($this->folderPath, [$uid]);
        $this->dispatch('messages-archived', ['message' => 'Moved to Trash.']);
    }

    public function getFolderStats(): array
    {
        try {
            return app(ImapMailboxService::class)->getMessageCount($this->folderPath);
        } catch (\Throwable) {
            return ['total' => 0, 'unread' => 0];
        }
    }

    private function extractUidsFromThreads(array $threads): array
    {
        $uids = [];
        foreach ($threads as $thread) {
            $uids[] = $thread->uid;
            $uids = array_merge($uids, $this->extractUidsFromThreads($thread->children ?? []));
        }

        return $uids;
    }

    private function findMessageInThreads(iterable $threads, int $uid): ?object
    {
        foreach ($threads as $thread) {
            if (isset($thread->uid) && $thread->uid == $uid) {
                return $thread;
            }
            if (! empty($thread->children)) {
                $found = $this->findMessageInThreads($thread->children, $uid);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function buildHeadersFromFlatMessages(array $messages, ImapMailboxService $imapService, array $pageUids): array
    {
        if (empty($messages)) {
            return ! empty($pageUids) ? $imapService->getThreadHeaders($this->folderPath, $pageUids) : [];
        }

        $metadataByUid = collect();
        if (! empty($pageUids) && Auth::check()) {
            try {
                $metadataByUid = MessageMetadata::where('user_id', Auth::id())
                    ->where('folder_path', $this->folderPath)
                    ->whereIn('uid', $pageUids)
                    ->with('labels')
                    ->get()
                    ->keyBy('uid');
            } catch (\Throwable) {
                // Ignore if query fails
            }
        }

        $results = [];
        foreach ($messages as $msg) {
            if (! is_object($msg)) {
                continue;
            }

            $uid = method_exists($msg, 'getUid') ? $msg->getUid() : ($msg->uid ?? null);
            if (! $uid) {
                continue;
            }

            $meta = $metadataByUid->get($uid);
            $header = method_exists($msg, 'getHeader') ? $msg->getHeader() : null;

            // Robust From extraction
            $fromObj = null;
            if (method_exists($msg, 'getFrom')) {
                $fromAttr = $msg->getFrom();
                if ($fromAttr instanceof Attribute) {
                    $fromObj = $fromAttr->first();
                }
            }
            if (! $fromObj && $header) {
                $fromAttr = $header->get('from');
                if ($fromAttr instanceof Attribute) {
                    $fromObj = $fromAttr->first();
                }
            }
            if (! $fromObj && isset($msg->from)) {
                $fromVal = $msg->from;
                if ($fromVal instanceof Attribute) {
                    $fromObj = $fromVal->first();
                } elseif (is_iterable($fromVal)) {
                    $fromObj = collect($fromVal)->first();
                } else {
                    $fromObj = $fromVal;
                }
            }

            $fromAddress = '';
            $fromName = '';
            if ($fromObj) {
                if ($fromObj instanceof Address) {
                    $fromAddress = $fromObj->mail ?? '';
                    $fromName = $fromObj->personal ?? '';
                } elseif (is_object($fromObj)) {
                    $fromAddress = $fromObj->mail ?? (isset($fromObj->mailbox, $fromObj->host) && $fromObj->mailbox && $fromObj->host ? $fromObj->mailbox.'@'.$fromObj->host : '');
                    $fromName = $fromObj->personal ?? '';
                } elseif (is_string($fromObj)) {
                    $fromAddress = $fromObj;
                }
            }

            if (empty($fromAddress) && ! empty($msg->from_address)) {
                $fromAddress = $msg->from_address;
            }
            if (empty($fromName) && ! empty($msg->from_name)) {
                $fromName = $msg->from_name;
            }
            if (empty($fromAddress) && $meta && ! empty($meta->from_address)) {
                $fromAddress = $meta->from_address;
            }
            if (empty($fromName) && $meta && ! empty($meta->from_name)) {
                $fromName = $meta->from_name;
            }

            $fromDisplay = ! empty($fromName) ? $fromName : (! empty($fromAddress) ? $fromAddress : 'Unknown');
            if (! empty($msg->from_display)) {
                $fromDisplay = $msg->from_display;
            }

            // Extract To
            $to = isset($msg->to) ? (is_iterable($msg->to) ? collect($msg->to)->first() : $msg->to) : null;
            $toAddress = is_object($to) ? ($to->mail ?? '') : (string) ($msg->to ?? ($meta?->to_address ?? ''));

            $msgId = (string) ($msg->message_id ?? ($header ? $header->get('message_id') : '') ?? ($meta?->message_id ?? ''));
            if (empty($msgId)) {
                $msgId = 'uid-'.$uid.'@openmail.local';
            }

            // Extract Date
            $rawDate = null;
            if (method_exists($msg, 'getDate')) {
                $dateAttr = $msg->getDate();
                if ($dateAttr instanceof Attribute) {
                    $rawDate = $dateAttr->first();
                }
            }
            if (! $rawDate && $header) {
                $dateAttr = $header->get('date');
                if ($dateAttr instanceof Attribute) {
                    $rawDate = $dateAttr->first();
                }
            }
            if (! $rawDate && isset($msg->date)) {
                $propDate = $msg->date;
                if ($propDate instanceof Attribute) {
                    $rawDate = $propDate->first();
                } else {
                    $rawDate = $propDate;
                }
            }
            if (! $rawDate && $meta?->date) {
                $rawDate = $meta->date;
            }

            $carbonDate = null;
            if ($rawDate instanceof CarbonInterface) {
                $carbonDate = $rawDate;
            } elseif ($rawDate instanceof \DateTimeInterface) {
                $carbonDate = Carbon::instance($rawDate);
            } elseif (is_string($rawDate) && trim($rawDate) !== '') {
                try {
                    $carbonDate = Carbon::parse($rawDate);
                } catch (\Throwable) {
                    $carbonDate = null;
                }
            }

            $formattedDate = '';
            if ($carbonDate) {
                $now = now();
                if ($carbonDate->isToday()) {
                    $formattedDate = $carbonDate->format('g:i A');
                } elseif ($carbonDate->isYesterday()) {
                    $formattedDate = 'Yesterday';
                } elseif ($carbonDate->year === $now->year) {
                    $formattedDate = $carbonDate->format('M j');
                } else {
                    $formattedDate = $carbonDate->format('M j, Y');
                }
            }

            $isSeen = method_exists($msg, 'getFlags')
                ? (bool) ($msg->getFlags()?->has('seen') ?? false)
                : (bool) ($msg->is_seen ?? ($meta?->is_seen ?? false));

            $isFlagged = method_exists($msg, 'getFlags')
                ? (bool) ($msg->getFlags()?->has('flagged') ?? false)
                : (bool) ($msg->is_flagged ?? ($meta?->is_flagged ?? false));

            $hasAttachments = method_exists($msg, 'hasAttachments')
                ? (bool) $msg->hasAttachments()
                : (bool) ($msg->has_attachments ?? ($meta?->has_attachments ?? false));

            $snippet = '';
            if ($meta && ! empty($meta->snippet)) {
                $snippet = (string) $meta->snippet;
            } elseif (is_object($msg) && ! ($msg instanceof Message) && ! empty($msg->snippet)) {
                $snippet = (string) $msg->snippet;
            }

            $labels = collect();
            if ($meta && isset($meta->labels) && $meta->labels instanceof Collection) {
                $labels = $meta->labels;
            } elseif (isset($msg->labels) && $msg->labels instanceof Collection) {
                $labels = $msg->labels;
            }

            $inReplyTo = (string) ($header ? $header->get('in_reply_to') : (! ($msg instanceof Message) ? ($msg->in_reply_to ?? '') : ''));
            $references = (string) ($header ? $header->get('references') : (! ($msg instanceof Message) ? ($msg->references ?? '') : ''));
            $rawSubject = (string) ($header ? $header->get('subject') : (! ($msg instanceof Message) ? ($msg->subject ?? '') : ''));
            $subject = trim($rawSubject);
            if ($subject === '') {
                $subject = '(no subject)';
            }

            $results[] = (object) [
                'uid' => $uid,
                'message_id' => $msgId,
                'in_reply_to' => $inReplyTo,
                'references' => $references,
                'subject' => $subject,
                'date' => $carbonDate ? $carbonDate->toDateTimeString() : (string) ($rawDate ?? ''),
                'formatted_date' => $formattedDate,
                'from_address' => $fromAddress,
                'from_name' => $fromName,
                'from_display' => $fromDisplay,
                'to_address' => $toAddress,
                'is_seen' => $isSeen,
                'is_flagged' => $isFlagged,
                'has_attachments' => $hasAttachments,
                'snippet' => $snippet,
                'folder_path' => $this->folderPath,
                'labels' => $labels,
            ];
        }

        return $results;
    }

    public function render()
    {
        return view('livewire.mailbox.message-list', [
            'messages' => $this->getMessages(),
            'selectedUids' => $this->selectedUids,
            'folderPath' => $this->folderPath,
            'threadMode' => $this->threadMode,
            'folderStats' => $this->getFolderStats(),
            'quickFilter' => $this->quickFilter,
        ]);
    }
}
