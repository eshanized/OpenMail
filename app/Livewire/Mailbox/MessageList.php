<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ImapMailboxService;
use App\Services\ThreadBuilder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class MessageList extends Component
{
    use WithPagination;

    public string $folderPath = 'INBOX';
    public string $sortBy = 'date';
    public string $sortDir = 'desc';
    public array $selectedUids = [];
    public string $threadMode = 'threaded'; // 'threaded' | 'flat'
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

    public function getMessages()
    {
        if ($this->threadMode === 'threaded') {
            return $this->getThreadedMessages();
        }

        return $this->getFlatMessages();
    }

    public function getFlatMessages(): LengthAwarePaginator
    {
        return app(ImapMailboxService::class)->getMessages(
            $this->folderPath,
            $this->sortBy,
            $this->sortDir,
            $this->getPage(),
            25
        );
    }

    public function getThreadedMessages(): array
    {
        $imapService = app(ImapMailboxService::class);
        $page = $this->getPage();
        $perPage = 25;

        // Get flat messages for current page
        $flatMessages = $imapService->getMessages(
            $this->folderPath,
            $this->sortBy,
            $this->sortDir,
            $page,
            $perPage
        );

        // Collect UIDs from current page
        $pageUids = $flatMessages->pluck('uid')->toArray();

        // Fetch headers needed for threading
        $threadHeaders = $imapService->getThreadHeaders($this->folderPath, $pageUids);

        // Convert to collection for ThreadBuilder
        $messages = collect($threadHeaders);

        // Resolve missing parents from thread_header_cache
        $threadBuilder = app(ThreadBuilder::class);
        
        // Find all Message-IDs referenced in In-Reply-To/References that aren't in current set
        $allMessageIds = $messages->pluck('message_id')->filter()->toArray();
        $referencedIds = [];
        
        foreach ($messages as $msg) {
            if (!empty($msg->in_reply_to)) {
                $referencedIds[] = trim($msg->in_reply_to, '<> ');
            }
            if (!empty($msg->references)) {
                $refs = array_filter(array_map('trim', explode(' ', $msg->references)));
                $referencedIds = array_merge($referencedIds, $refs);
            }
        }
        
        $missingIds = array_values(array_unique(array_diff($referencedIds, $allMessageIds)));
        
        if (!empty($missingIds)) {
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
        $message = $this->findMessageInThreads($messages, $uid);
        
        if (!$message) {
            return;
        }
        
        $newValue = !$message->is_flagged;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$uid], '\\Flagged', $newValue);
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

    private function findMessageInThreads(array $threads, int $uid): ?object
    {
        foreach ($threads as $thread) {
            if ($thread->uid == $uid) {
                return $thread;
            }
            $found = $this->findMessageInThreads($thread->children ?? [], $uid);
            if ($found) {
                return $found;
            }
        }
        return null;
    }

    public function render()
    {
        return view('livewire.mailbox.message-list', [
            'messages' => $this->getMessages(),
            'selectedUids' => $this->selectedUids,
            'folderPath' => $this->folderPath,
            'threadMode' => $this->threadMode,
        ]);
    }
}
