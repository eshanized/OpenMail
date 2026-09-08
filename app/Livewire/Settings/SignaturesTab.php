<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Signature;
use App\Services\SignatureService;
use App\Http\Requests\SignatureRequest;

class SignaturesTab extends Component
{
    /** @var \Illuminate\Database\Eloquent\Collection */
    public $signatures;

    public bool $showModal = false;
    public ?int $editingSignatureId = null;
    public string $modalTitle = 'New Signature';
    public string $name = '';
    public array $contentJson = [];
    public bool $isDefault = false;

    public function mount(): void
    {
        $this->loadSignatures();
    }

    public function loadSignatures(): void
    {
        $user = auth()->user();
        $this->signatures = app(SignatureService::class)->getAll($user);
    }

    public function openCreateModal(): void
    {
        $this->editingSignatureId = null;
        $this->modalTitle = 'New Signature';
        $this->name = '';
        $this->contentJson = ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
        $this->isDefault = false;
        $this->showModal = true;
    }

    public function openEditModal(int $signatureId): void
    {
        $signature = Signature::where('id', $signatureId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $this->editingSignatureId = $signature->id;
        $this->modalTitle = 'Edit Signature';
        $this->name = $signature->name;
        $this->contentJson = $signature->content_json ?? ['type' => 'doc', 'content' => [['type' => 'paragraph']]];
        $this->isDefault = $signature->is_default;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->editingSignatureId = null;
    }

    public function saveSignature(): void
    {
        $this->validate([
            'name' => 'required|string|max:100',
            'content_json' => 'required|array',
        ]);

        $service = app(SignatureService::class);
        $user = auth()->user();

        $data = [
            'name' => $this->name,
            'content_json' => $this->contentJson,
            'is_default' => $this->isDefault,
        ];

        if ($this->editingSignatureId) {
            $signature = Signature::where('id', $this->editingSignatureId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            $service->update($signature, $data);

            // Handle default toggle
            if ($this->isDefault) {
                $service->setDefault($signature);
            }
        } else {
            $signature = $service->create($data, $user);

            // If marked as default on create, set it
            if ($this->isDefault) {
                $service->setDefault($signature);
            }
        }

        $this->closeModal();
        $this->loadSignatures();
        $this->dispatch('signature-updated');
    }

    public function deleteSignature(int $signatureId): void
    {
        $signature = Signature::where('id', $signatureId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        app(SignatureService::class)->delete($signature);
        $this->loadSignatures();
        $this->dispatch('signature-updated');
    }

    public function setDefaultSignature(int $signatureId): void
    {
        $signature = Signature::where('id', $signatureId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        app(SignatureService::class)->setDefault($signature);
        $this->loadSignatures();
        $this->dispatch('signature-updated');
    }

    public function updateContentJson(array $contentJson): void
    {
        $this->contentJson = $contentJson;
    }

    public function render()
    {
        return view('livewire.settings.signatures-tab');
    }
}
