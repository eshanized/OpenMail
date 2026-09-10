<?php

namespace App\Livewire\Mailbox;

use App\Models\Label;
use App\Services\LabelService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LabelModal extends Component
{
    public bool $show = false;

    public ?Label $label = null;

    public string $name = '';

    public string $color = '#2563EB';

    public array $palette = [];

    protected $listeners = [
        'openCreateLabelModal' => 'openCreateModal',
        'openEditLabelModal' => 'openEditModal',
    ];

    public function mount(): void
    {
        $this->palette = LabelService::PALETTE;
    }

    public function openCreateModal(): void
    {
        $this->label = null;
        $this->name = '';
        $this->color = '#2563EB';
        $this->show = true;
    }

    public function openEditModal(Label $label): void
    {
        $this->label = $label;
        $this->name = $label->name;
        $this->color = $label->color;
        $this->show = true;
    }

    public function save(): void
    {
        $service = app(LabelService::class);

        try {
            if ($this->label) {
                $service->update($this->label->id, auth()->id(), $this->name, $this->color);
            } else {
                $service->create(auth()->id(), $this->name, $this->color);
            }

            $this->show = false;
            $this->dispatch('labelSaved');
            $this->dispatch('toast', $this->label ? 'Label updated' : 'Label created', 'success');
        } catch (ValidationException $e) {
            $this->dispatch('toast', $e->getMessage(), 'error');
        }
    }

    public function closeModal(): void
    {
        $this->show = false;
    }

    public function selectColor(string $color): void
    {
        $this->color = $color;
    }

    public function render()
    {
        return view('livewire.mailbox.label-modal', [
            'palette' => $this->palette,
        ]);
    }
}
