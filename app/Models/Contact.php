<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $table = 'contacts';

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'notes',
        'avatar_color',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_contact');
    }

    protected static function booted(): void
    {
        static::creating(function (self $contact) {
            if (empty($contact->avatar_color)) {
                $contact->avatar_color = self::colorFromEmail($contact->email);
            }
        });
    }

    public static function colorFromEmail(string $email): string
    {
        $palette = [
            'bg-blue-500',
            'bg-green-600',
            'bg-red-600',
            'bg-yellow-600',
            'bg-purple-600',
            'bg-pink-600',
            'bg-orange-600',
            'bg-teal-600',
            'bg-indigo-600',
            'bg-gray-500',
        ];

        return $palette[crc32(strtolower($email)) % 10];
    }
}
