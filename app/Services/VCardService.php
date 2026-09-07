<?php

namespace App\Services;

use Sabre\VObject\Reader;
use Sabre\VObject\Writer;
use Sabre\VObject\Component\VCard;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Support\Collection;

class VCardService
{
    public function import(string $content, int $userId, string $conflictStrategy = 'skip'): array
    {
        $results = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Split content by BEGIN:VCARD to handle multiple vCards
        $vcardBlocks = preg_split('/(?=BEGIN:VCARD)/i', $content);
        $vcardBlocks = array_filter($vcardBlocks, fn($block) => trim($block) !== '');

        foreach ($vcardBlocks as $block) {
            try {
                $vcard = Reader::read($block);
            } catch (\Throwable $e) {
                $results['errors'][] = 'Invalid vCard format: ' . $e->getMessage();
                continue;
            }

            $cards = [];
            if ($vcard instanceof VCard) {
                $cards[] = $vcard;
            } else {
                foreach ($vcard->children() as $card) {
                    if ($card instanceof VCard) {
                        $cards[] = $card;
                    }
                }
            }

            foreach ($cards as $card) {
                $email = (string) ($card->EMAIL ?? '');
                if (!$email) {
                    $results['errors'][] = 'Missing email in vCard';
                    continue;
                }

                $email = strtolower(trim($email));

                $existing = Contact::where('user_id', $userId)->where('email', $email)->first();

                if ($existing && $conflictStrategy === 'skip') {
                    $results['skipped']++;
                    continue;
                }

                $data = [
                    'user_id' => $userId,
                    'name' => (string) ($card->FN ?? ''),
                    'email' => $email,
                    'phone' => (string) ($card->TEL ?? ''),
                    'notes' => (string) ($card->NOTE ?? ''),
                ];

                if ($existing && $conflictStrategy === 'update') {
                    $existing->update($data);
                    $this->syncGroups($existing, $card);
                    $results['updated']++;
                } elseif ($existing && $conflictStrategy === 'duplicate') {
                    // For duplicate strategy, create a new contact with modified email
                    $counter = 1;
                    $baseEmail = $email;
                    $emailParts = explode('@', $email);
                    while (Contact::where('user_id', $userId)->where('email', $email)->exists()) {
                        $email = $emailParts[0] . '+' . $counter . '@' . $emailParts[1];
                        $counter++;
                    }
                    $data['email'] = $email;
                    $contact = Contact::create($data);
                    $this->syncGroups($contact, $card);
                    $results['imported']++;
                } else {
                    $contact = Contact::create($data);
                    $this->syncGroups($contact, $card);
                    $results['imported']++;
                }
            }
        }

        return $results;
    }

    public function export(int $userId): string
    {
        $contacts = Contact::with('groups')->where('user_id', $userId)->get();

        if ($contacts->isEmpty()) {
            return '';
        }

        $vcard = new \Sabre\VObject\Component\VCard();

        foreach ($contacts as $contact) {
            $card = new VCard();
            $card->VERSION = '3.0';
            $card->add('FN', $contact->name);
            $card->add('EMAIL', $contact->email);
            if ($contact->phone) {
                $card->add('TEL', $contact->phone);
            }
            if ($contact->notes) {
                $card->add('NOTE', $contact->notes);
            }
            if ($contact->groups->isNotEmpty()) {
                $categories = $contact->groups->pluck('name')->implode(',');
                $card->add('CATEGORIES', $categories);
            }
            $vcard->add($card);
        }

        return Writer::write($vcard);
    }

    private function syncGroups(Contact $contact, VCard $card): void
    {
        $categories = (string) ($card->CATEGORIES ?? '');
        if (!$categories) {
            return;
        }

        $groupNames = array_map('trim', explode(',', $categories));
        $groupNames = array_filter($groupNames);

        if (empty($groupNames)) {
            return;
        }

        $groups = collect();
        foreach ($groupNames as $groupName) {
            $group = ContactGroup::firstOrCreate(
                ['user_id' => $contact->user_id, 'name' => $groupName],
                ['color' => 'bg-blue-500']
            );
            $groups->push($group);
        }

        $contact->groups()->sync($groups->pluck('id'));
    }
}