<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ImapMailboxService;
use Illuminate\Pagination\LengthAwarePaginator;

class MessageList extends Component
{
    use WithPagination;

    public string $folderPath = 'INBOX';
    public string $sortBy = 'date';
    public string $sortDir = 'desc';
    public array $selectedUids = [];
    protected $paginationTheme = 'tailwind';

    protected $listeners = [
        'folder-changed' => 'onFolderChanged',
        'selection-cleared' => 'clearSelection',
    ];

    public function onFolderChanged(string $folderPath): void
    {
        $this->folderPath = $folderPath;
        $this->resetPage('messages-page');
        $this->clearSelection();
    }

    public function getMessages(): LengthAwarePaginator
    {
        return app(ImapMailboxService::class)->getMessages(
            $this->folderPath,
            $this->sortBy,
            $this->sortDir,
            $this->getPage(),
            25
        );
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
        $this->selectedUids = $messages->pluck('uid')->toArray();
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
        $message = $messages->firstWhere('uid', $uid);
        
        if (!$message) {
            return;
        }
        
        $newValue = !$message->is_flagged;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$uid], '\\Flagged', $newValue);
    }

    public function render()
    {
        return view('livewire.mailbox.message-list', [
            'messages' => $this->getMessages(),
            'selectedUids' => $this->selectedUids,
            'folderPath' => $this->folderPath,
        ]);
    }
}