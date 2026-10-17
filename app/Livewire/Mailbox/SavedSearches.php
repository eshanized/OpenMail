<?php

declare(strict_types=1);

namespace App\Livewire\Mailbox;

use App\Models\SavedSearch;
use Livewire\Component;
use Livewire\WithPagination;

class SavedSearches extends Component
{
    use WithPagination;

    public function runSearch(int $savedSearchId)
    {
        $savedSearch = SavedSearch::query()
            ->where('user_id', auth()->id())
            ->findOrFail($savedSearchId);

        return redirect()->route('search', $savedSearch->searchParameters());
    }

    public function deleteSearch(int $savedSearchId): void
    {
        $deleted = SavedSearch::query()
            ->where('user_id', auth()->id())
            ->whereKey($savedSearchId)
            ->delete();

        $this->dispatch(
            'toast',
            $deleted ? 'Saved search deleted.' : 'Saved search not found.',
            $deleted ? 'success' : 'warning'
        );

        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.mailbox.saved-searches', [
            'savedSearches' => SavedSearch::query()
                ->where('user_id', auth()->id())
                ->orderBy('name')
                ->paginate(20),
        ]);
    }
}
