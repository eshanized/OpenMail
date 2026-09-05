<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\ImapMailboxService;

class MessageToolbar extends Component
{
    public array $selectedUids = [];
    public string $folderPath = 'INBOX';
    public array $folders = [];

    protected $listeners = [
        'selection-cleared' => 'clearSelection',
    ];

    public function mount(): void
    {
        $this->loadFolders();
    }

    public function loadFolders(): void
    {
        $this->folders = app(ImapMailboxService::class)->getCachedFolders();
    }

    public function clearSelection(): void
    {
        $this->selectedUids = [];
    }

    public function bulkMarkRead(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->setFlag($this->folderPath, $this->selectedUids, '\\Seen', true);
        $this->dispatch('selection-cleared');
    }

    public function bulkMarkUnread(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->setFlag($this->folderPath, $this->selectedUids, '\\Seen', false);
        $this->dispatch('selection-cleared');
    }

    public function bulkStar(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->setFlag($this->folderPath, $this->selectedUids, '\\Flagged', true);
        $this->dispatch('selection-cleared');
    }

    public function bulkUnstar(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->setFlag($this->folderPath, $this->selectedUids, '\\Flagged', false);
        $this->dispatch('selection-cleared');
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->deleteMessages($this->folderPath, $this->selectedUids);
        $this->dispatch('selection-cleared');
    }

    public function bulkMove(string $destinationPath): void
    {
        if (empty($this->selectedUids)) {
            return;
        }
        app(ImapMailboxService::class)->moveMessages($this->folderPath, $this->selectedUids, $destinationPath);
        $this->dispatch('selection-cleared');
    }

    public function render()
    {
        return view('livewire.mailbox.message-toolbar', [
            'selectedUids' => $this->selectedUids,
            'folderPath' => $this->folderPath,
            'folders' => $this->folders,
        ]);
    }
}