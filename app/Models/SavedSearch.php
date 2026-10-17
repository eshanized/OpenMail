<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'query',
        'filters',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function searchParameters(): array
    {
        $allowedKeys = ['folder', 'date_from', 'date_to', 'has_attachment', 'is_seen', 'is_flagged', 'labels'];
        $filters = array_intersect_key($this->filters ?? [], array_flip($allowedKeys));

        return ['q' => $this->query, ...$filters];
    }
}
