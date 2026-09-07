<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\LabelService;
use App\Models\Label;
use Illuminate\Support\Collection;

class LabelSidebar extends Component
{
    public Collection $labels;
    public bool $showModal = false;
    public ?Label $editingLabel = null;
    public bool $activeTab = false;
    public bool $isLoading = true;
    public ?string $activeLabelId = null;

    protected $listeners = [
        'labelSaved' => 'refreshLabels',
        'labelDeleted' => 'refreshLabels',
        'setActiveTab' => 'setActiveTab',
        'label-selected' => 'onLabelSelected',
    ];

    public function mount(): void
    {
        $this->labels = collect();
        if ($this->activeTab) {
            $this->refreshLabels();
        }
    }

    public function updatedActiveTab(bool $active): void
    {
        $this->setActiveTab($active);
    }

    public function setActiveTab(bool $active): void
    {
        $this->activeTab = $active;
        if ($active && $this->labels->isEmpty()) {
            $this->refreshLabels();
        }
    }

    public function refreshLabels(): void
    {
        $this->isLoading = true;
        $service = app(LabelService::class);
        $this->labels = $service->getForUser(auth()->id());
        $this->isLoading = false;
    }

    public function openCreateModal(): void
    {
        $this->editingLabel = null;
        $this->showModal = true;
    }

    public function openEditModal(Label $label): void
    {
        $this->editingLabel = $label;
        $this->showModal = true;
    }

    public function deleteLabel(int $labelId): void
    {
        $service = app(LabelService::class);
        $service->delete($labelId, auth()->id());
        $this->refreshLabels();
        $this->dispatch('labelDeleted');
        $this->dispatch('toast', 'Label deleted', 'success');
    }

    public function selectLabel(string $labelId): void
    {
        $this->activeLabelId = $this->activeLabelId === $labelId ? null : $labelId;
        $this->dispatch('label-selected', labelId: $this->activeLabelId);
    }

    public function onLabelSelected(?string $labelId): void
    {
        $this->activeLabelId = $labelId;
    }

    public function render()
    {
        return view('livewire.mailbox.label-sidebar', [
            'labels' => $this->labels,
            'activeLabelId' => $this->activeLabelId,
        ]);
    }
}
