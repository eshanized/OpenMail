<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageMetadata extends Model
{
    use HasFactory;

    protected $table = 'message_metadata';

    protected $fillable = [
        'user_id',
        'folder_path',
        'uid',
        'message_id',
        'subject',
        'from_address',
        'from_name',
        'to_address',
        'date',
        'snippet',
        'has_attachments',
        'is_seen',
        'is_flagged',
        'size',
    ];

    protected $casts = [
        'date' => 'datetime',
        'is_seen' => 'boolean',
        'is_flagged' => 'boolean',
        'has_attachments' => 'boolean',
        'size' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFromDisplayAttribute(): string
    {
        return $this->from_name ?? $this->from_address;
    }

    public function getFormattedDateAttribute(): string
    {
        $date = $this->date;
        if (!$date) {
            return '';
        }

        $now = now();
        if ($date->isToday()) {
            return $date->format('g:i A');
        } elseif ($date->isYesterday()) {
            return 'Yesterday';
        } elseif ($date->year === $now->year) {
            return $date->format('M j');
        } else {
            return $date->format('M j, Y');
        }
    }
}