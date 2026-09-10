<?php

namespace App\Services;

use App\Exceptions\DuplicateContactException;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Support\Collection;

class ContactService
{
    public function create(int $userId, array $data): Contact
    {
        $this->validateEmail($data['email']);

        $existing = Contact::where('user_id', $userId)
            ->where('email', strtolower(trim($data['email'])))
            ->first();

        if ($existing) {
            throw new DuplicateContactException("Contact with email {$data['email']} already exists");
        }

        $data['email'] = strtolower(trim($data['email']));
        $data['user_id'] = $userId;
        $data['usage_count'] = 0;

        return Contact::create($data);
    }

    public function update(Contact $contact, array $data): Contact
    {
        if (isset($data['email'])) {
            $this->validateEmail($data['email']);
            $newEmail = strtolower(trim($data['email']));

            if ($newEmail !== $contact->email) {
                $existing = Contact::where('user_id', $contact->user_id)
                    ->where('email', $newEmail)
                    ->where('id', '!=', $contact->id)
                    ->first();

                if ($existing) {
                    throw new DuplicateContactException("Contact with email {$newEmail} already exists");
                }

                $data['email'] = $newEmail;
            } else {
                unset($data['email']);
            }
        }

        $contact->update($data);

        return $contact->fresh() ?? $contact;
    }

    public function delete(Contact $contact): void
    {
        $contact->groups()->detach();
        $contact->delete();
    }

    public function getForUser(int $userId): Collection
    {
        return Contact::where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }

    public function search(int $userId, string $query): Collection
    {
        $escapedQuery = str_replace(['%', '_'], ['\\%', '\\_'], $query);

        return Contact::where('user_id', $userId)
            ->where(function ($q) use ($escapedQuery) {
                $q->where('email', 'LIKE', "{$escapedQuery}%")
                    ->orWhere('name', 'LIKE', "%{$escapedQuery}%");
            })
            ->orderByDesc('usage_count')
            ->get();
    }

    public function incrementUsage(Contact $contact): void
    {
        $contact->increment('usage_count');
    }

    public function getGroups(int $userId): Collection
    {
        return ContactGroup::forUser($userId)
            ->withCount('contacts')
            ->orderBy('name')
            ->get();
    }

    public function createGroup(int $userId, array $data): ContactGroup
    {
        $data['user_id'] = $userId;

        return ContactGroup::create($data);
    }

    private function validateEmail(string $email): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$email}");
        }
    }
}
