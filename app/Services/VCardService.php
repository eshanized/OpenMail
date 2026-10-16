<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Support\Facades\DB;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use Sabre\VObject\Writer;

class VCardService
{
    private const MAX_IMPORT_BYTES = 5_242_880; // 5 MB

    private const MAX_CONTACTS = 1000;

    public function import(string $content, int $userId, string $conflictStrategy = 'skip'): array
    {
        $results = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        if (strlen($content) > self::MAX_IMPORT_BYTES) {
            throw new \InvalidArgumentException('vCard file exceeds the maximum allowed size of 5MB.');
        }

        // Split content by BEGIN:VCARD to handle multiple vCards
        $vcardBlocks = preg_split('/(?=BEGIN:VCARD)/i', $content);
        $vcardBlocks = array_filter($vcardBlocks, fn ($block) => trim($block) !== '');

        $processedCount = 0;

        foreach ($vcardBlocks as $block) {
            if ($processedCount >= self::MAX_CONTACTS) {
                $results['errors'][] = 'Maximum contact import limit ('.self::MAX_CONTACTS.') reached.';
                break;
            }

            try {
                $vcard = Reader::read($block);
            } catch (\Throwable $e) {
                $results['errors'][] = 'Invalid vCard format: '.$e->getMessage();

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
                if ($processedCount >= self::MAX_CONTACTS) {
                    $results['errors'][] = 'Maximum contact import limit ('.self::MAX_CONTACTS.') reached.';
                    break 2;
                }

                $email = (string) ($card->EMAIL ?? '');
                $email = strtolower(trim($email));

                if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $results['errors'][] = 'Invalid or missing email in vCard: '.($email ?: '(empty)');

                    continue;
                }

                $processedCount++;

                $existing = Contact::where('user_id', $userId)->where('email', $email)->first();

                if ($existing && $conflictStrategy === 'skip') {
                    $results['skipped']++;

                    continue;
                }

                $data = [
                    'user_id' => $userId,
                    'name' => mb_substr((string) ($card->FN ?? ''), 0, 255),
                    'email' => $email,
                    'phone' => mb_substr((string) ($card->TEL ?? ''), 0, 50),
                    'notes' => mb_substr((string) ($card->NOTE ?? ''), 0, 1000),
                ];

                DB::transaction(function () use (&$results, $existing, $conflictStrategy, $userId, $data, $card) {
                    if ($existing && $conflictStrategy === 'update') {
                        $existing->update($data);
                        $this->syncGroups($existing, $card);
                        $results['updated']++;
                    } elseif ($existing && $conflictStrategy === 'duplicate') {
                        $counter = 1;
                        $emailParts = explode('@', $data['email']);
                        $newEmail = $data['email'];
                        while ($counter <= 100 && Contact::where('user_id', $userId)->where('email', $newEmail)->exists()) {
                            $newEmail = $emailParts[0].'+'.$counter.'@'.$emailParts[1];
                            $counter++;
                        }
                        $data['email'] = $newEmail;
                        $contact = Contact::create($data);
                        $this->syncGroups($contact, $card);
                        $results['imported']++;
                    } else {
                        $contact = Contact::create($data);
                        $this->syncGroups($contact, $card);
                        $results['imported']++;
                    }
                });
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

        $vcard = new VCard;

        foreach ($contacts as $contact) {
            $card = new VCard;
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
        if (! $categories) {
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
