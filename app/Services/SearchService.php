<?php

namespace App\Services;

use App\Models\MessageMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Search messages for a user with filters.
     *
     * @param  int  $userId  The authenticated user ID
     * @param  string  $query  Search query
     * @param  array  $filters  Optional filters (folder, date_from, date_to, has_attachment, is_seen, is_flagged, labels)
     * @param  int  $perPage  Results per page (max 50, D-09/T-04-10)
     */
    public function search(int $userId, string $query, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $perPage = min($perPage, 50);
        $sanitizedQuery = $this->sanitizeQuery($query);

        if ($this->supportsFullText()) {
            // MySQL/MariaDB: use Scout with FULLTEXT search
            $search = MessageMetadata::search($sanitizedQuery)
                ->where('user_id', $userId)
                ->query(function ($q) use ($filters) {
                    $this->applyFilters($q, $filters);
                });

            return $search->paginate($perPage);
        }

        // SQLite (testing) / other: use LIKE-based search
        return $this->searchWithLike($userId, $sanitizedQuery, $filters, $perPage);
    }

    /**
     * Instant search returning limited results for dropdown (D-08).
     *
     * @param  int  $userId  The authenticated user ID
     * @param  string  $query  Search query
     * @param  int  $limit  Maximum results (default 8)
     */
    public function instantSearch(int $userId, string $query, int $limit = 8): Collection
    {
        $sanitizedQuery = $this->sanitizeQuery($query);

        if ($this->supportsFullText()) {
            return MessageMetadata::search($sanitizedQuery)
                ->where('user_id', $userId)
                ->take($limit)
                ->get([
                    'id', 'subject', 'from_address', 'from_name', 'to_address',
                    'snippet', 'folder_path', 'date', 'has_attachments', 'is_seen',
                ]);
        }

        return $this->searchWithLike($userId, $sanitizedQuery, [], $limit, [
            'id', 'subject', 'from_address', 'from_name', 'to_address',
            'snippet', 'folder_path', 'date', 'has_attachments', 'is_seen',
        ]);
    }

    /**
     * Determine if the database supports FULLTEXT search.
     */
    private function supportsFullText(): bool
    {
        return config('database.default') !== 'sqlite';
    }

    /**
     * LIKE-based search fallback for databases without FULLTEXT support.
     */
    private function searchWithLike(
        int $userId,
        string $query,
        array $filters = [],
        int $limit = 25,
        ?array $columns = null
    ): Collection|LengthAwarePaginator {
        $searchableColumns = ['subject', 'from_address', 'from_name', 'to_address', 'snippet', 'body_text'];

        $q = MessageMetadata::query()->where('user_id', $userId);

        if (! empty($query)) {
            $words = array_filter(explode(' ', $query));
            foreach ($words as $word) {
                // Escape LIKE wildcards to prevent broader-than-intended search results
                $escapedWord = str_replace(['%', '_'], ['\\%', '\\_'], $word);
                $q->where(function ($subQuery) use ($searchableColumns, $escapedWord) {
                    foreach ($searchableColumns as $col) {
                        $subQuery->orWhere($col, 'LIKE', '%'.$escapedWord.'%');
                    }
                });
            }
        }

        $this->applyFilters($q, $filters);

        // When columns are specified, return limited collection (instant search)
        if ($columns) {
            return $q->orderByDesc('date')->take($limit)->get($columns);
        }

        // Otherwise return paginated results
        return $q->orderByDesc('date')->paginate($limit);
    }

    /**
     * Highlight search matches in a snippet string.
     * Wraps matches in <mark> tags with yellow background styling.
     *
     * @param  string  $snippet  The text to highlight
     * @param  string  $query  The search query
     * @return string The highlighted text
     */
    public function highlightMatches(string $snippet, string $query): string
    {
        if (empty($query) || empty($snippet)) {
            return $snippet;
        }

        // Escape regex special characters in query
        $escapedQuery = preg_quote($query, '/');

        // Split query into words for multi-word highlighting
        $words = array_filter(explode(' ', trim($query)));

        if (empty($words)) {
            return $snippet;
        }

        // Build pattern for all query words
        $patterns = array_map(fn ($word) => preg_quote($word, '/'), $words);
        $pattern = '/('.implode('|', $patterns).')/iu';

        // Wrap matches in <mark> tags — applied AFTER any HTML sanitization
        return preg_replace($pattern, '<mark class="bg-yellow-100 text-yellow-900 px-0.5 rounded">$1</mark>', $snippet);
    }

    /**
     * Sanitize search query to prevent MySQL FULLTEXT boolean mode injection (T-04-06).
     * Escapes: + - > < * " ( ) ~
     *
     * @param  string  $query  Raw user query
     * @return string Sanitized query safe for MATCH...AGAINST
     */
    public function sanitizeQuery(string $query): string
    {
        // Remove FULLTEXT boolean mode operators that could manipulate search
        // These characters are: + - > < * " ( ) ~
        $sanitized = preg_replace('/[+\-><*"()~]/', ' ', $query);

        // Normalize whitespace
        $sanitized = preg_replace('/\s+/', ' ', trim($sanitized));

        return $sanitized;
    }

    /**
     * Apply filter constraints to a query builder.
     *
     * @param  Builder  $query
     * @return Builder
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['folder'])) {
            $query->where('folder_path', $filters['folder']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }

        if (isset($filters['has_attachment']) && $filters['has_attachment'] === true) {
            $query->where('has_attachments', true);
        }

        if (isset($filters['is_seen'])) {
            $query->where('is_seen', $filters['is_seen']);
        }

        if (isset($filters['is_flagged'])) {
            $query->where('is_flagged', $filters['is_flagged']);
        }

        if (! empty($filters['labels']) && is_array($filters['labels'])) {
            $query->whereHas('labels', function ($q) use ($filters) {
                $q->whereIn('labels.id', $filters['labels']);
            });
        }
    }
}
