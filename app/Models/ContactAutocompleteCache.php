<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactAutocompleteCache extends Model
{
    use HasFactory;

    protected $table = 'contact_autocomplete_cache';

    protected $fillable = [
        'user_id',
        'email',
        'name',
        'frequency',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'frequency' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}