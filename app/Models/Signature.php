<?php

namespace App\Models;

use App\Services\MessageSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tiptap\Editor;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'content_json', 'content_html', 'is_default'];

    protected $casts = [
        'content_json' => 'array',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Signature $signature): void {
            // Sanitize HTML before save using tiptap-php to render JSON → HTML
            if ($signature->content_json && ! $signature->content_html) {
                try {
                    if (class_exists(Editor::class)) {
                        $editor = new Editor;
                        $html = $editor->setContent($signature->content_json)->getHTML();
                        $sanitizer = app(MessageSanitizer::class);
                        $signature->content_html = $sanitizer->sanitizeHtml($html);
                    } else {
                        $signature->content_html = e(json_encode($signature->content_json));
                    }
                } catch (\Throwable $e) {
                    // Fallback: if tiptap-php is not available, store JSON-encoded content
                    $signature->content_html = e(json_encode($signature->content_json));
                }
            }

            // Enforce single default per user (T-05-18)
            if ($signature->is_default) {
                static::where('user_id', $signature->user_id)
                    ->where('id', '!=', $signature->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
