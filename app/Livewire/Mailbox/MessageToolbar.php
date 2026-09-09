<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\ImapMailboxService;
use App\Services\LabelService;

class MessageToolbar extends Component
{
    public array $selectedUids = [];
    public string $folderPath = 'INBOX';
    public array $folders = [];
    public bool $showArchiveToast = false;
    public string $archiveToastMessage = '';
    public array $archiveRevertData = [];

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

    /**
     * Archive selected messages: IMAP MOVE + label sync + undo toast.
     */
    public function archiveSelected(): void
    {
        if (empty($this->selectedUids)) {
            return;
        }

        $imapService = app(ImapMailboxService::class);
        $labelService = app(LabelService::class);
        $userId = auth()->id();

        // Store revert data for undo
        $this->archiveRevertData = [
            'uids' => $this->selectedUids,
            'sourceFolder' => $this->folderPath,
            'archivedAt' => now()->toIso8601String(),
        ];

        // Ensure Archive and Inbox labels exist
        $archiveLabel = $labelService->ensureArchiveLabel($userId);
        $inboxLabel = $labelService->ensureInboxLabel($userId);

        // Move messages to Archive folder via IMAP
        try {
            $archivePath = $imapService->getOrCreateArchiveFolder();
            if ($archivePath && $this->folderPath !== $archivePath) {
                $imapService->moveMessages($this->folderPath, $this->selectedUids, $archivePath);
            }

            // Find message metadata IDs for label operations
            $messageIds = \App\Models\MessageMetadata::where('user_id', $userId)
                ->where('folder_path', $this->folderPath)
                ->whereIn('uid', $this->selectedUids)
                ->pluck('id')
                ->toArray();

            // Remove Inbox label and add Archive label
            if (!empty($messageIds)) {
                $labelService->removeFromMessages($inboxLabel->id, $messageIds, $userId);
                $labelService->applyToMessages($archiveLabel->id, $messageIds, $userId);
            }

            // Show undo toast for 5 seconds
            $this->showArchiveToast = true;
            $this->archiveToastMessage = count($this->selectedUids) === 1
                ? 'Message archived.'
                : count($this->selectedUids) . ' messages archived.';

            $this->dispatch('selection-cleared');
            $this->dispatch('messages-archived');
        } catch (\Exception $e) {
            $this->dispatch('toast', 'Archive failed: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Undo the last archive operation.
     */
    public function undoArchive(): void
    {
        if (empty($this->archiveRevertData)) {
            return;
        }

        $imapService = app(ImapMailboxService::class);
        $labelService = app(LabelService::class);
        $userId = auth()->id();

        $uids = $this->archiveRevertData['uids'];
        $sourceFolder = $this->archiveRevertData['sourceFolder'];

        // Ensure labels exist
        $archiveLabel = $labelService->ensureArchiveLabel($userId);
        $inboxLabel = $labelService->ensureInboxLabel($userId);

        try {
            // Move messages back to source folder
            $imapService->moveMessageFromArchive($uids[0], $sourceFolder);
            // For multiple UIDs, move each one
            foreach (array_slice($uids, 1) as $uid) {
                $imapService->moveMessageFromArchive($uid, $sourceFolder);
            }

            // Find message metadata IDs
            $messageIds = \App\Models\MessageMetadata::where('user_id', $userId)
                ->whereIn('uid', $uids)
                ->pluck('id')
                ->toArray();

            // Restore labels: remove Archive, add Inbox
            if (!empty($messageIds)) {
                $labelService->removeFromMessages($archiveLabel->id, $messageIds, $userId);
                $labelService->applyToMessages($inboxLabel->id, $messageIds, $userId);
            }

            $this->showArchiveToast = false;
            $this->archiveRevertData = [];
            $this->dispatch('toast', 'Archive undone', 'success');
            $this->dispatch('messages-restored');
        } catch (\Exception $e) {
            $this->dispatch('toast', 'Undo failed: ' . $e->getMessage(), 'error');
        }
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