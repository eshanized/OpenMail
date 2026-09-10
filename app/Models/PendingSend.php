<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingSend extends Model
{
    use HasFactory;

    protected $table = 'pending_sends';

    protected $fillable = [
        'user_id',
        'message_json',
        'mime_message',
        'send_at',
        'status',
        'sent_folder_uid',
        'retry_count',
    ];

    protected $casts = [
        'message_json' => 'array',
        'send_at' => 'datetime',
        'status' => 'string',
        'retry_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('send_at', '<=', now());
    }
}
