<?php

namespace App\Livewire\Mailbox;

use App\Exceptions\DuplicateContactException;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Services\ContactService;
use Livewire\Component;

class ContactModal extends Component
{
    public bool $showModal = true;

    public ?Contact $contact = null;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $notes = '';

    public array $selectedGroups = [];

    public array $availableGroups = [];

    public string $newGroupName = '';

    public bool $isCreating = false;

    protected $listeners = [
        'closeModal' => 'closeModal',
    ];

    public function mount(?Contact $contact = null): void
    {
        if ($contact && ! $contact->exists) {
            $contact = null;
        }

        $userId = auth()->id();
        if ($contact && $contact->user_id !== $userId) {
            abort(403, 'Unauthorized.');
        }

        $this->contact = $contact;
        $this->loadGroups();

        if ($contact) {
            $this->name = $contact->name ?? '';
            $this->email = $contact->email ?? '';
            $this->phone = $contact->phone ?? '';
            $this->notes = $contact->notes ?? '';
            $this->selectedGroups = $contact->groups->pluck('id')->toArray();
        }
    }

    public function loadGroups(): void
    {
        $this->availableGroups = ContactGroup::forUser(auth()->id())
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    public function save(): void
    {
        $userId = auth()->id();
        if (! $userId) {
            abort(403, 'You must be logged in to manage contacts.');
        }

        if ($this->contact && $this->contact->user_id !== $userId) {
            abort(403, 'Unauthorized.');
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ]);

        $service = app(ContactService::class);

        try {
            if ($this->contact) {
                // Update existing contact
                $contact = $service->update($this->contact, [
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'notes' => $this->notes,
                ]);
            } else {
                $contact = $service->create($userId, [
                    'name' => $this->name,
                    'email' => $this->email,
                    'phone' => $this->phone,
                    'notes' => $this->notes,
                ]);
            }

            // Sync groups ensuring user ownership
            $validGroupIds = ContactGroup::forUser($userId)
                ->whereIn('id', $this->selectedGroups)
                ->pluck('id')
                ->toArray();
            $contact->groups()->sync($validGroupIds);

            $this->dispatch('contactSaved');
            $this->dispatch('toast', 'Contact saved', 'success');
            $this->closeModal();
        } catch (DuplicateContactException $e) {
            $this->addError('email', $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            $this->addError('email', $e->getMessage());
        }
    }

    public function delete(): void
    {
        $userId = auth()->id();
        if (! $userId) {
            abort(403, 'Unauthorized.');
        }

        if ($this->contact) {
            if ($this->contact->user_id !== $userId) {
                abort(403, 'Unauthorized.');
            }

            $service = app(ContactService::class);
            $service->delete($this->contact);
            $this->dispatch('contactDeleted');
            $this->dispatch('toast', 'Contact deleted', 'success');
            $this->closeModal();
        }
    }

    public function addGroup(): void
    {
        if (empty($this->newGroupName)) {
            return;
        }

        $group = ContactGroup::firstOrCreate(
            ['user_id' => auth()->id(), 'name' => $this->newGroupName],
            ['color' => 'bg-blue-500']
        );

        $this->availableGroups[] = $group->toArray();
        $this->selectedGroups[] = $group->id;
        $this->newGroupName = '';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->dispatch('closeContactModal');
    }

    public function render()
    {
        return view('livewire.mailbox.contact-modal');
    }
}
