<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

class MessageMetadata extends Model
{
    use HasFactory, Searchable;

    protected $table = 'message_metadata';

    protected $fillable = [
        'user_id',
        'folder_path',
        'uid',
        'message_id',
        'in_reply_to',
        'references',
        'subject',
        'from_address',
        'from_name',
        'to_address',
        'date',
        'snippet',
        'body_text',
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

    protected $appends = [
        'thread_id',
    ];

    /**
     * Thread children relationship (virtual - populated by ThreadBuilder).
     * Not a database relationship; set at runtime when building threads.
     */
    public function getChildrenAttribute()
    {
        return $this->attributes['children'] ?? collect();
    }

    public function setChildrenAttribute($value)
    {
        $this->attributes['children'] = $value;
    }

    /**
     * Latest message date in thread (virtual - populated by ThreadBuilder).
     */
    public function getLatestDateAttribute()
    {
        return $this->attributes['latestDate'] ?? $this->date;
    }

    public function setLatestDateAttribute($value)
    {
        $this->attributes['latestDate'] = $value;
    }

    /**
     * Unread count in thread (virtual - populated by ThreadBuilder).
     */
    public function getUnreadCountAttribute()
    {
        return $this->attributes['unreadCount'] ?? ($this->is_seen ? 0 : 1);
    }

    public function setUnreadCountAttribute($value)
    {
        $this->attributes['unreadCount'] = $value;
    }

    /**
     * Virtual thread_id for Alpine.js tracking (same as message_id for root).
     */
    public function getThreadIdAttribute(): string
    {
        return $this->message_id ?? "uid-{$this->uid}";
    }

    /**
     * Get the index name for the model (per-user isolation per D-05).
     */
    public function searchableAs(): string
    {
        return 'message_metadata_' . $this->user_id;
    }

    /**
     * Get the value that should be used to index the model (D-05 fields).
     *
     * @return array<string, mixed>
     */
    #[SearchUsingFullText(['subject', 'from_address', 'from_name', 'to_address', 'snippet', 'body_text'])]
    #[SearchUsingPrefix(['message_id'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'folder_path' => $this->folder_path,
            'uid' => $this->uid,
            'message_id' => $this->message_id,
            'subject' => $this->subject,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'to_address' => $this->to_address,
            'date' => $this->date?->timestamp,
            'snippet' => $this->snippet,
            'body_text' => $this->body_text,
            'has_attachments' => $this->has_attachments,
            'is_seen' => $this->is_seen,
            'is_flagged' => $this->is_flagged,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * Extract first 500 chars of plain text from HTML or plain text body.
     * Used during sync to populate body_text column (RESEARCH.md Open Question 2).
     */
    public static function extractBodyText(?string $htmlOrText): string
    {
        if (empty($htmlOrText)) {
            return '';
        }

        // Strip HTML tags
        $text = strip_tags($htmlOrText);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace (collapse multiple spaces/newlines to single space)
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        // Truncate to 500 chars
        if (mb_strlen($text) > 500) {
            $text = mb_substr($text, 0, 500);
        }

        return $text;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Labels attached to this message.
     */
    public function labels()
    {
        return $this->belongsToMany(Label::class, 'message_labels', 'message_metadata_id', 'label_id')
            ->using(MessageLabel::class)
            ->withPivot('user_id');
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

    /**
     * Scope to filter messages that have a specific label.
     */
    public function scopeWithLabel(Builder $query, int $labelId): Builder
    {
        return $query->whereHas('labels', fn($q) => $q->where('labels.id', $labelId));
    }

    /**
     * Scope to filter messages that don't have a specific label.
     */
    public function scopeWithoutLabel(Builder $query, int $labelId): Builder
    {
        return $query->whereDoesntHave('labels', fn($q) => $q->where('labels.id', $labelId));
    }
}