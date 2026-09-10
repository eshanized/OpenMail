<?php

namespace App\Services;

use App\Models\Label;
use App\Models\MessageMetadata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LabelService
{
    /**
     * 10-color palette from UI-SPEC §Label Color Palette
     */
    public const PALETTE = [
        'Blue' => '#2563EB',
        'Green' => '#16A34A',
        'Red' => '#DC2626',
        'Yellow' => '#CA8A04',
        'Purple' => '#9333EA',
        'Pink' => '#DB2777',
        'Orange' => '#EA580C',
        'Teal' => '#0D9488',
        'Indigo' => '#4F46E5',
        'Gray' => '#6B7280',
    ];

    /**
     * Create a new label for the user.
     *
     * @param  string  $color  Hex color from palette
     *
     * @throws ValidationException
     */
    public function create(int $userId, string $name, string $color): Label
    {
        $this->validateCreate($name, $color, $userId);

        return DB::transaction(function () use ($userId, $name, $color) {
            return Label::create([
                'user_id' => $userId,
                'name' => $name,
                'color' => $color,
            ]);
        });
    }

    /**
     * Update an existing label.
     *
     * @throws ValidationException
     */
    public function update(int $labelId, int $userId, ?string $name = null, ?string $color = null): Label
    {
        $label = Label::where('id', $labelId)->where('user_id', $userId)->firstOrFail();

        if ($name !== null || $color !== null) {
            $this->validateUpdate($label, $name, $color);
        }

        return DB::transaction(function () use ($label, $name, $color) {
            if ($name !== null) {
                $label->name = $name;
            }
            if ($color !== null) {
                $label->color = $color;
            }
            $label->save();

            return $label->fresh();
        });
    }

    /**
     * Delete a label and remove it from all messages.
     */
    public function delete(int $labelId, int $userId): void
    {
        $label = Label::where('id', $labelId)->where('user_id', $userId)->firstOrFail();

        DB::transaction(function () use ($label) {
            // Detach from all messages (cascade on pivot will handle this via foreign keys)
            $label->messages()->detach();
            $label->delete();
        });
    }

    /**
     * Apply label to messages.
     *
     * @param  array<int>  $messageIds
     * @param  int  $userId  The authenticated user ID (required for authorization)
     */
    public function applyToMessages(int $labelId, array $messageIds, int $userId): void
    {
        $label = Label::where('id', $labelId)->where('user_id', $userId)->firstOrFail();

        // Verify all messages belong to the same user as the label
        $messageIds = MessageMetadata::where('user_id', $label->user_id)
            ->whereIn('id', $messageIds)
            ->pluck('id')
            ->toArray();

        if (empty($messageIds)) {
            return;
        }

        // syncWithoutDetaching with pivot data for user_id
        $syncData = [];
        foreach ($messageIds as $messageId) {
            $syncData[$messageId] = ['user_id' => $label->user_id];
        }
        $label->messages()->syncWithoutDetaching($syncData);
    }

    /**
     * Remove label from messages.
     *
     * @param  array<int>  $messageIds
     * @param  int  $userId  The authenticated user ID (required for authorization)
     */
    public function removeFromMessages(int $labelId, array $messageIds, int $userId): void
    {
        $label = Label::where('id', $labelId)->where('user_id', $userId)->firstOrFail();

        $label->messages()->detach($messageIds);
    }

    /**
     * Get all labels for a user with unread and total message counts.
     *
     * @return Collection<int, Label>
     */
    public function getForUser(int $userId): Collection
    {
        return Label::forUser($userId)
            ->withCount([
                'messages as unread_count' => function ($query) {
                    $query->where('is_seen', false);
                },
                'messages as total_count',
            ])
            ->orderBy('name')
            ->get();
    }

    /**
     * Get the 10-color palette from UI-SPEC.
     *
     * @return array<string, string>
     */
    public function getPalette(): array
    {
        return self::PALETTE;
    }

    /**
     * Find or create the "Archive" label for a user.
     */
    public function ensureArchiveLabel(int $userId): Label
    {
        return Label::firstOrCreate(
            ['user_id' => $userId, 'name' => 'Archive'],
            ['color' => self::PALETTE['Gray']]
        );
    }

    /**
     * Find or create the "Inbox" label for a user.
     */
    public function ensureInboxLabel(int $userId): Label
    {
        return Label::firstOrCreate(
            ['user_id' => $userId, 'name' => 'Inbox'],
            ['color' => self::PALETTE['Blue']]
        );
    }

    /**
     * Validate label creation.
     *
     * @throws ValidationException
     */
    private function validateCreate(string $name, string $color, int $userId): void
    {
        $errors = [];

        if (empty(trim($name))) {
            $errors['name'] = 'Label name is required.';
        } elseif (mb_strlen($name) > 50) {
            $errors['name'] = 'Label name must not exceed 50 characters.';
        } else {
            // Check uniqueness (case-insensitive)
            $exists = Label::where('user_id', $userId)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->exists();

            if ($exists) {
                $errors['name'] = 'A label with this name already exists.';
            }
        }

        if (! in_array($color, self::PALETTE, true)) {
            $errors['color'] = 'Invalid color. Must be one of the 10 palette colors.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Validate label update.
     *
     * @throws ValidationException
     */
    private function validateUpdate(Label $label, ?string $name, ?string $color): void
    {
        $errors = [];

        if ($name !== null) {
            if (empty(trim($name))) {
                $errors['name'] = 'Label name is required.';
            } elseif (mb_strlen($name) > 50) {
                $errors['name'] = 'Label name must not exceed 50 characters.';
            } else {
                // Check uniqueness (case-insensitive) excluding current label
                $exists = Label::where('user_id', $label->user_id)
                    ->where('id', '!=', $label->id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->exists();

                if ($exists) {
                    $errors['name'] = 'A label with this name already exists.';
                }
            }
        }

        if ($color !== null && ! in_array($color, self::PALETTE, true)) {
            $errors['color'] = 'Invalid color. Must be one of the 10 palette colors.';
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
