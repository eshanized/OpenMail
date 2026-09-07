<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->belongsToMany(MessageMetadata::class, 'message_labels', 'label_id', 'message_metadata_id')
            ->using(MessageLabel::class)
            ->withPivot('user_id');
    }

    /**
     * Scope to filter labels by user.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
