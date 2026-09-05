<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $table = 'folders';

    protected $fillable = [
        'user_id',
        'path',
        'name',
        'role',
        'total_count',
        'unread_count',
        'uidvalidity',
        'has_children',
        'parent_path',
        'sort_order',
    ];

    protected $casts = [
        'has_children' => 'boolean',
        'uidvalidity' => 'integer',
        'total_count' => 'integer',
        'unread_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStandardRoles($query)
    {
        return $query->whereNotNull('role');
    }

    public function scopeCustom($query)
    {
        return $query->whereNull('role');
    }

    public function isUnread(): bool
    {
        return $this->unread_count > 0;
    }
}