<?php

namespace App\Services;

use App\Models\Signature;
use App\Models\User;

class SignatureService
{
    /**
     * Get the default signature for a user.
     */
    public function getDefault(User $user): ?Signature
    {
        return $user->signatures()->where('is_default', true)->first();
    }

    /**
     * Create a new signature for a user.
     */
    public function create(array $data, User $user): Signature
    {
        // If this is the user's first signature, make it default automatically
        if ($user->signatures()->count() === 0) {
            $data['is_default'] = true;
        }

        return $user->signatures()->create($data);
    }

    /**
     * Update an existing signature.
     */
    public function update(Signature $signature, array $data): Signature
    {
        $signature->update($data);
        return $signature->fresh();
    }

    /**
     * Delete a signature. If it was the default, promote the newest remaining.
     */
    public function delete(Signature $signature): void
    {
        $wasDefault = $signature->is_default;
        $userId = $signature->user_id;
        $signature->delete();

        // If deleted was default, promote newest remaining signature
        if ($wasDefault) {
            $nextDefault = Signature::where('user_id', $userId)
                ->latest()
                ->first();
            if ($nextDefault) {
                $nextDefault->update(['is_default' => true]);
            }
        }
    }

    /**
     * Set a signature as the default, unsetting all others for this user.
     */
    public function setDefault(Signature $signature): void
    {
        $signature->user->signatures()->where('id', '!=', $signature->id)->update(['is_default' => false]);
        $signature->is_default = true;
        $signature->save();
    }

    /**
     * Get all signatures for a user, ordered by default first then by name.
     */
    public function getAll(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->signatures()
            ->orderByRaw('is_default DESC, name ASC')
            ->get();
    }
}
