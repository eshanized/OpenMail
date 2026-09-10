<?php

namespace App\Livewire\Mailbox;

use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContactSidebar extends Component
{
    public string $search = '';

    public Collection $contacts;

    public ?Contact $selectedContact = null;

    public bool $showModal = false;

    public bool $showImportModal = false;

    public bool $activeTab = false;

    public bool $isLoading = true;

    protected $listeners = [
        'contactSaved' => 'refreshContacts',
        'contactDeleted' => 'refreshContacts',
        'contactsImported' => 'refreshContacts',
        'setActiveTab' => 'setActiveTab',
    ];

    public function mount(): void
    {
        $this->contacts = collect();
        if ($this->activeTab) {
            $this->refreshContacts();
        }
    }

    public function setActiveTab(bool $active): void
    {
        $this->activeTab = $active;
        if ($active && $this->contacts->isEmpty()) {
            $this->refreshContacts();
        }
    }

    public function updatedSearch(): void
    {
        $this->searchContacts();
    }

    public function searchContacts(): void
    {
        if (empty($this->search)) {
            $this->refreshContacts();

            return;
        }

        $service = app(ContactService::class);
        $this->contacts = $service->search(auth()->id(), $this->search);
    }

    public function refreshContacts(): void
    {
        $this->isLoading = true;
        $service = app(ContactService::class);
        $this->contacts = $service->getForUser(auth()->id());
        $this->isLoading = false;
    }

    public function openCreateModal(): void
    {
        $this->selectedContact = null;
        $this->showModal = true;
    }

    public function openEditModal(Contact $contact): void
    {
        $this->selectedContact = $contact;
        $this->showModal = true;
    }

    public function deleteContact(Contact $contact): void
    {
        $service = app(ContactService::class);
        $service->delete($contact);
        $this->dispatch('contactDeleted');
        $this->dispatch('toast', 'Contact deleted', 'success');
    }

    public function openImportModal(): void
    {
        $this->showImportModal = true;
    }

    public function render()
    {
        return view('livewire.mailbox.contact-sidebar');
    }
}
