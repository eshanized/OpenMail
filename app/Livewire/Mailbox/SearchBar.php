<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\SearchService;
use Illuminate\Support\Collection;

class SearchBar extends Component
{
    public string $query = '';
    public Collection $results;
    public bool $loading = false;
    public bool $open = false;
    public int $highlightedIndex = -1;

    protected $listeners = [
        'focus-search' => 'focusSearch',
    ];

    public function mount(): void
    {
        $this->results = collect();
    }

    /**
     * Perform instant search with debounced input (D-08: 300ms debounce).
     * Called from Alpine.js after debounce timer.
     */
    public function instantSearch(): void
    {
        if (strlen(trim($this->query)) < 2) {
            $this->results = collect();
            $this->open = false;
            $this->loading = false;
            return;
        }

        $this->loading = true;

        $searchService = app(SearchService::class);
        $this->results = $searchService->instantSearch(
            auth()->id(),
            $this->query,
            8
        );

        $this->open = true;
        $this->loading = false;
        $this->highlightedIndex = -1;
    }

    /**
     * Navigate to message view when a result is clicked.
     */
    public function openResult(int $id): \Illuminate\Http\RedirectResponse
    {
        $result = $this->results->firstWhere('id', $id);
        if (!$result) {
            return redirect()->back();
        }

        return redirect()->route('message.show', [
            'folderPath' => $result->folder_path,
            'uid' => $result->uid,
        ]);
    }

    /**
     * Focus the search input (triggered by '/' keyboard shortcut).
     */
    public function focusSearch(): void
    {
        $this->dispatch('focus-search-input');
    }

    /**
     * Close the dropdown.
     */
    public function closeDropdown(): void
    {
        $this->open = false;
        $this->highlightedIndex = -1;
    }

    public function render()
    {
        return view('livewire.mailbox.search-bar');
    }
}
