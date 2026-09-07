<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $query = $request->input('q', '');

        if (empty(trim($query))) {
            return redirect()->route('mailbox');
        }

        $filters = [
            'folder' => $request->input('folder'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'has_attachment' => $request->boolean('has_attachment'),
            'is_seen' => $request->input('is_seen') !== null ? $request->boolean('is_seen') : null,
            'is_flagged' => $request->boolean('is_flagged'),
            'labels' => $request->input('labels') ? array_map('intval', $request->input('labels')) : [],
        ];

        // Remove empty filters
        $filters = array_filter($filters, function ($v) {
            if (is_array($v)) return !empty($v);
            return $v !== null && $v !== '' && $v !== false;
        });

        $searchService = app(SearchService::class);
        $results = $searchService->search(
            auth()->id(),
            $query,
            $filters,
            25
        );

        // Get user's folders for filter sidebar
        $folders = cache()->remember(
            'user_folders_' . auth()->id(),
            3600,
            fn() => \App\Models\MessageMetadata::where('user_id', auth()->id())
                ->distinct()
                ->pluck('folder_path')
                ->sort()
                ->values()
        );

        // Get user's labels for filter sidebar
        $labels = app(\App\Services\LabelService::class)->getForUser(auth()->id());

        return view('livewire.mailbox.search-results-page', [
            'query' => $query,
            'results' => $results,
            'filters' => $filters,
            'folders' => $folders,
            'labels' => $labels,
        ]);
    }
}
