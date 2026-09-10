<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThreadHeaderCache extends Model
{
    use HasFactory;

    protected $table = 'thread_header_cache';

    protected $fillable = [
        'user_id',
        'message_id',
        'in_reply_to',
        'references',
        'subject',
        'date',
        'folder_path',
        'uid',
        'expires_at',
    ];

    protected $casts = [
        'date' => 'datetime',
        'expires_at' => 'datetime',
        'uid' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
