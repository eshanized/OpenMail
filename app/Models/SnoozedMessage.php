<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SnoozedMessage extends Model
{
    use HasFactory;

    public const STATUS_SNOOZED = 'snoozed';

    public const STATUS_RETURNED = 'returned';

    protected $fillable = [
        'user_id',
        'folder_path',
        'uid',
        'message_id',
        'subject',
        'sender',
        'wake_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'uid' => 'integer',
            'wake_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_SNOOZED)
            ->where('wake_at', '>', now());
    }
}
