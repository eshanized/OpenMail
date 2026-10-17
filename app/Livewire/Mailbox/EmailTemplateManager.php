<?php

namespace App\Livewire\Mailbox;

use App\Models\EmailTemplate;
use Illuminate\Validation\Rule;
use Livewire\Component;

class EmailTemplateManager extends Component
{
    public bool $showForm = false;

    public ?int $editingTemplateId = null;

    public string $name = '';

    public string $subject = '';

    public string $body = '';

    public function createTemplate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editTemplate(int $templateId): void
    {
        $template = EmailTemplate::query()
            ->where('user_id', auth()->id())
            ->find($templateId);

        if (! $template) {
            $this->dispatch('toast', 'Template not found.', 'warning');

            return;
        }

        $this->editingTemplateId = (int) $template->id;
        $this->name = $template->name;
        $this->subject = $template->subject ?? '';
        $this->body = $template->body;
        $this->showForm = true;
    }

    public function saveTemplate(): void
    {
        $uniqueName = Rule::unique('email_templates', 'name')
            ->where(fn ($query) => $query->where('user_id', auth()->id()));

        if ($this->editingTemplateId !== null) {
            $uniqueName->ignore($this->editingTemplateId);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        $attributes = [
            'name' => trim($validated['name']),
            'subject' => trim($validated['subject'] ?? '') ?: null,
            'body' => $validated['body'],
        ];

        if ($this->editingTemplateId !== null) {
            EmailTemplate::query()
                ->where('user_id', auth()->id())
                ->whereKey($this->editingTemplateId)
                ->firstOrFail()
                ->update($attributes);
        } else {
            EmailTemplate::create([
                ...$attributes,
                'user_id' => auth()->id(),
            ]);
        }

        $this->dispatch('toast', 'Email template saved.', 'success');
        $this->resetForm();
    }

    public function deleteTemplate(int $templateId): void
    {
        $deleted = EmailTemplate::query()
            ->where('user_id', auth()->id())
            ->whereKey($templateId)
            ->delete();

        $this->dispatch(
            'toast',
            $deleted ? 'Email template deleted.' : 'Email template not found.',
            $deleted ? 'success' : 'warning'
        );

        if ($this->editingTemplateId === $templateId) {
            $this->resetForm();
        }
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->showForm = false;
        $this->editingTemplateId = null;
        $this->name = '';
        $this->subject = '';
        $this->body = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mailbox.email-template-manager', [
            'templates' => EmailTemplate::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->get(),
        ]);
    }
}
