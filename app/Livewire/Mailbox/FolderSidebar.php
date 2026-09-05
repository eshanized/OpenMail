<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\ImapMailboxService;
use App\Services\FolderMapper;

class FolderSidebar extends Component
{
    public array $folders = [];
    public string $currentFolder = 'INBOX';

    public function mount(): void
    {
        $this->loadFolders();
    }

    public function loadFolders(): void
    {
        $this->folders = app(ImapMailboxService::class)->getCachedFolders();
    }

    public function selectFolder(string $path): void
    {
        $this->currentFolder = $path;
        $this->dispatch('folder-changed', folderPath: $path);
    }

    public function refreshFolders(): void
    {
        app(ImapMailboxService::class)->refreshFolderCache('*');
        $this->loadFolders();
    }

    public function render()
    {
        return view('livewire.mailbox.folder-sidebar', [
            'folders' => $this->folders,
            'currentFolder' => $this->currentFolder,
        ]);
    }
}