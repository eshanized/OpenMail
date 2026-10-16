<?php

namespace App\Models;

use App\Services\MessageSanitizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'content_json', 'content_html', 'is_default'];

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
            // Generate clean HTML from content_json if JSON changed or HTML is empty
            if ($signature->isDirty('content_json') || empty($signature->content_html)) {
                if (! empty($signature->content_json)) {
                    $generatedHtml = self::convertJsonToHtml($signature->content_json);
                    if ($generatedHtml !== '') {
                        $signature->content_html = $generatedHtml;
                    }
                }
            }

            // Always sanitize HTML before save
            if ($signature->content_html) {
                $sanitizer = app(MessageSanitizer::class);
                $signature->content_html = $sanitizer->sanitizeHtml($signature->content_html);
            }

            // Enforce single default per user (T-05-18)
            if ($signature->is_default) {
                static::where('user_id', $signature->user_id)
                    ->where('id', '!=', $signature->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    /**
     * Convert TipTap JSON structure into clean, safe HTML without external dependencies.
     */
    public static function convertJsonToHtml(array|string|null $doc): string
    {
        if (empty($doc)) {
            return '';
        }

        if (is_string($doc)) {
            $decoded = json_decode($doc, true);
            if (! is_array($decoded)) {
                return nl2br(e($doc));
            }
            $doc = $decoded;
        }

        return self::renderNode($doc);
    }

    private static function renderNode(array $node): string
    {
        $type = $node['type'] ?? '';
        $content = $node['content'] ?? [];
        $renderedContent = '';

        if (is_array($content)) {
            foreach ($content as $child) {
                if (is_array($child)) {
                    $renderedContent .= self::renderNode($child);
                }
            }
        }

        return match ($type) {
            'doc' => $renderedContent,
            'paragraph' => '<p>'.($renderedContent !== '' ? $renderedContent : '<br>').'</p>',
            'heading' => '<h'.min(6, max(1, (int) ($node['attrs']['level'] ?? 1))).'>'.$renderedContent.'</h'.min(6, max(1, (int) ($node['attrs']['level'] ?? 1))).'>',
            'bulletList' => '<ul>'.$renderedContent.'</ul>',
            'orderedList' => '<ol>'.$renderedContent.'</ol>',
            'listItem' => '<li>'.$renderedContent.'</li>',
            'blockquote' => '<blockquote>'.$renderedContent.'</blockquote>',
            'hardBreak' => '<br>',
            'text' => self::renderTextNode($node),
            default => $renderedContent,
        };
    }

    private static function renderTextNode(array $node): string
    {
        $text = htmlspecialchars((string) ($node['text'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $marks = $node['marks'] ?? [];

        if (is_array($marks)) {
            foreach ($marks as $mark) {
                $markType = $mark['type'] ?? '';
                $text = match ($markType) {
                    'bold' => '<strong>'.$text.'</strong>',
                    'italic' => '<em>'.$text.'</em>',
                    'underline' => '<u>'.$text.'</u>',
                    'strike' => '<s>'.$text.'</s>',
                    'link' => '<a href="'.htmlspecialchars((string) ($mark['attrs']['href'] ?? '#'), ENT_QUOTES, 'UTF-8').'" target="_blank" rel="noopener noreferrer">'.$text.'</a>',
                    default => $text,
                };
            }
        }

        return $text;
    }
}
