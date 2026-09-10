<?php

namespace App\Livewire\Mailbox;

use App\Services\VCardService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

class ContactImportModal extends Component
{
    use WithFileUploads;

    public bool $showModal = true;

    public $importFile = null;

    public array $preview = [];

    public string $conflictStrategy = 'skip';

    public ?array $importResults = null;

    public bool $isProcessing = false;

    public array $selectedContacts = [];

    public bool $selectAll = true;

    protected $listeners = [
        'closeModal' => 'closeModal',
    ];

    public function updatedImportFile(): void
    {
        if ($this->importFile) {
            $this->parsePreview();
        }
    }

    public function parsePreview(): void
    {
        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $service = app(VCardService::class);

            // Parse vCard to get preview
            $vcardBlocks = preg_split('/(?=BEGIN:VCARD)/i', $content);
            $vcardBlocks = array_filter($vcardBlocks, fn ($block) => trim($block) !== '');

            $this->preview = [];
            $this->selectedContacts = [];

            foreach ($vcardBlocks as $index => $block) {
                try {
                    $vcard = Reader::read($block);
                    if ($vcard instanceof VCard) {
                        $email = (string) ($vcard->EMAIL ?? '');
                        if ($email) {
                            $this->preview[] = [
                                'index' => $index,
                                'name' => (string) ($vcard->FN ?? ''),
                                'email' => strtolower(trim($email)),
                                'phone' => (string) ($vcard->TEL ?? ''),
                            ];
                            $this->selectedContacts[] = $index;
                        }
                    }
                } catch (\Throwable $e) {
                    // Skip invalid vCards
                    continue;
                }
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', 'Failed to parse vCard file: '.$e->getMessage(), 'error');
        }
    }

    public function toggleSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedContacts = array_column($this->preview, 'index');
        } else {
            $this->selectedContacts = [];
        }
    }

    public function confirmImport(): void
    {
        if (empty($this->selectedContacts)) {
            $this->dispatch('toast', 'No contacts selected for import', 'warning');

            return;
        }

        $this->isProcessing = true;

        try {
            $content = file_get_contents($this->importFile->getRealPath());
            $service = app(VCardService::class);

            // Filter vCard content to only include selected contacts
            $vcardBlocks = preg_split('/(?=BEGIN:VCARD)/i', $content);
            $vcardBlocks = array_filter($vcardBlocks, fn ($block) => trim($block) !== '');

            $filteredContent = '';
            foreach ($this->selectedContacts as $index) {
                if (isset($vcardBlocks[$index])) {
                    $filteredContent .= $vcardBlocks[$index];
                }
            }

            $this->importResults = $service->import($filteredContent, auth()->id(), $this->conflictStrategy);

            $this->dispatch('contactsImported');
            $this->dispatch('toast', "Imported: {$this->importResults['imported']}, Updated: {$this->importResults['updated']}, Skipped: {$this->importResults['skipped']}", 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', 'Import failed: '.$e->getMessage(), 'error');
        } finally {
            $this->isProcessing = false;
        }
    }

    public function exportVCard(): void
    {
        $service = app(VCardService::class);
        $vcardContent = $service->export(auth()->id());

        if (empty($vcardContent)) {
            $this->dispatch('toast', 'No contacts to export', 'warning');

            return;
        }

        // Create temporary file for download
        $tempFile = tempnam(sys_get_temp_dir(), 'vcard_');
        file_put_contents($tempFile, $vcardContent);

        $this->dispatch('download-vcard', [
            'path' => $tempFile,
            'name' => 'contacts.vcf',
        ]);

        $this->dispatch('toast', 'vCard exported successfully', 'success');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('closeImportModal');
    }

    public function render()
    {
        return view('livewire.mailbox.contact-import-modal');
    }
}
