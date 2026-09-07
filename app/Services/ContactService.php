<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Exceptions\DuplicateContactException;

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
        return $contact->fresh();
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
        return Contact::where('user_id', $userId)
            ->where(function ($q) use ($query) {
                $q->where('email', 'LIKE', "{$query}%")
                    ->orWhere('name', 'LIKE', "%{$query}%");
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$email}");
        }
    }
}