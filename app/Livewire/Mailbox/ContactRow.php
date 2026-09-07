<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Models\Contact;

class ContactRow extends Component
{
    public Contact $contact;

    public function openContactModal(): void
    {
        $this->dispatch('openEditModal', $this->contact->id);
    }

    public function deleteContact(): void
    {
        $this->dispatch('deleteContact', $this->contact->id);
    }

    public function render()
    {
        return view('livewire.mailbox.contact-row');
    }
}