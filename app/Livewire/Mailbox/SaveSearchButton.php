<?php

declare(strict_types=1);

namespace App\Livewire\Mailbox;

use App\Models\Label;
use App\Models\SavedSearch;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SaveSearchButton extends Component
{
    public string $query = '';

    public array $filters = [];

    public string $name = '';

    public bool $showForm = false;

    public function mount(string $query, array $filters = []): void
    {
        $this->query = mb_substr(trim($query), 0, 255);
        $this->filters = $this->normalizeFilters($filters);
    }

    public function showSaveForm(): void
    {
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('saved_searches', 'name')
                    ->where(fn ($builder) => $builder->where('user_id', auth()->id())),
            ],
        ]);

        if (trim($this->query) === '') {
            $this->addError('query', 'A search query is required.');

            return;
        }

        $filters = $this->normalizeFilters($this->filters);

        SavedSearch::create([
            'user_id' => auth()->id(),
            'name' => trim($validated['name']),
            'query' => mb_substr(trim($this->query), 0, 255),
            'filters' => $filters,
        ]);

        $this->showForm = false;
        $this->name = '';
        $this->dispatch('toast', 'Search saved.', 'success');
    }

    protected function normalizeFilters(array $filters): array
    {
        $normalized = [];

        if (isset($filters['folder']) && is_string($filters['folder'])) {
            $folder = trim($filters['folder']);
            if ($folder !== '' && mb_strlen($folder) <= 255) {
                $normalized['folder'] = $folder;
            }
        }

        foreach (['date_from', 'date_to'] as $key) {
            $value = $filters[$key] ?? null;
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                if ($date && $date->format('Y-m-d') === $value) {
                    $normalized[$key] = $value;
                }
            }
        }

        foreach (['has_attachment', 'is_flagged'] as $key) {
            if (isset($filters[$key]) && filter_var($filters[$key], FILTER_VALIDATE_BOOLEAN)) {
                $normalized[$key] = true;
            }
        }

        if (array_key_exists('is_seen', $filters) && $filters['is_seen'] !== null) {
            $normalized['is_seen'] = filter_var($filters['is_seen'], FILTER_VALIDATE_BOOLEAN);
        }

        $labelIds = $filters['labels'] ?? [];
        if (is_array($labelIds)) {
            $labelIds = array_values(array_unique(array_filter(
                array_map(static fn ($id): int => is_numeric($id) ? (int) $id : 0, $labelIds),
                static fn (int $id): bool => $id > 0
            )));

            if ($labelIds !== []) {
                $normalized['labels'] = Label::query()
                    ->where('user_id', auth()->id())
                    ->whereIn('id', $labelIds)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
            }
        }

        return $normalized;
    }

    public function render()
    {
        return view('livewire.mailbox.save-search-button');
    }
}
