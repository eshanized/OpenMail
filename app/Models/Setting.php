<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'group',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a global setting (no user scope).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::whereNull('user_id')->where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a global setting (no user scope).
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['user_id' => null, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget("setting:{$key}");
    }

    /**
     * Get a user-scoped setting.
     */
    public static function getForUser(int $userId, string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:user:{$userId}:{$key}", 3600, function () use ($userId, $key, $default) {
            $setting = static::where('user_id', $userId)->where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Set a user-scoped setting.
     */
    public static function setForUser(int $userId, string $key, mixed $value, string $group = 'general'): void
    {
        static::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget("setting:user:{$userId}:{$key}");
    }
}
