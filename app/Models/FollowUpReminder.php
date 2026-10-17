<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpReminder extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = [
        'user_id',
        'folder_path',
        'uid',
        'message_id',
        'subject',
        'sender',
        'remind_at',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'uid' => 'integer',
            'remind_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
