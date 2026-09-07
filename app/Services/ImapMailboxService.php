<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\MessageCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\Folder as FolderModel;
use App\Models\MessageMetadata;

class ImapMailboxService
{
    private function getClient(): Client
    {
        $config = new \Webklex\PHPIMAP\Config([
            'default' => 'default',
            'accounts' => [
                'default' => [
                    'host' => config('openmail.imap.host'),
                    'port' => config('openmail.imap.port'),
                    'encryption' => config('openmail.imap.encryption'),
                    'username' => Auth::user()->email,
                    'password' => Crypt::decrypt(session('openmail:imap_password')),
                    'protocol' => 'imap',
                    'timeout' => 10,
                    'validate_cert' => true,
                ]
            ],
            'masks' => [
                'message' => \Webklex\PHPIMAP\Support\Masks\MessageMask::class,
                'attachment' => \Webklex\PHPIMAP\Support\Masks\AttachmentMask::class,
            ],
            'events' => [
                'message' => [],
                'folder' => [],
            ]
        ]);

        $client = new Client($config);
        $client->connect();
        return $client;
    }

    public function getFolders(): array
    {
        $client = $this->getClient();
        $mapper = app(FolderMapper::class);

        try {
            $folders = $client->getFolders();
            $result = [];

            foreach ($folders as $folder) {
                $role = $mapper->mapFolderRole($folder);
                $name = $mapper->mapFolderName($folder);
                $info = $folder->examine();

                $result[] = [
                    'path' => $folder->path,
                    'name' => $name,
                    'role' => $role,
                    'total' => (int) $info['exists'],
                    'uidvalidity' => $info['uidvalidity'],
                    'has_children' => $folder->hasChildren(),
                    'parent_path' => $this->getParentPath($folder->path),
                ];
            }

            return $result;
        } finally {
            $client->disconnect();
        }
    }

    public function getMessages(string $folderPath, string $sortBy = 'date', string $sortDir = 'desc', int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);

            $query = $folder->query()
                ->setFetchBody(false)
                ->leaveUnread();

            match ($sortBy) {
                'date' => $sortDir === 'desc' ? $query->setFetchOrderDesc() : $query->setFetchOrderAsc(),
                'sender' => $sortDir === 'asc' ? $query->setFetchOrderAsc() : $query->setFetchOrderDesc(),
                'subject' => $sortDir === 'asc' ? $query->setFetchOrderAsc() : $query->setFetchOrderDesc(),
                'size' => $sortDir === 'desc' ? $query->setFetchOrderDesc() : $query->setFetchOrderAsc(),
                default => $query->setFetchOrderDesc(),
            };

            return $query->paginate(
                per_page: $perPage,
                page: $page,
                page_name: 'messages-page'
            );
        } finally {
            $client->disconnect();
        }
    }

    public function getMessage(string $folderPath, int $uid): ?object
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $message = $folder->query()->getMessageByUid($uid);
            return $message;
        } finally {
            $client->disconnect();
        }
    }

    public function getMessageWithBody(string $folderPath, int $uid): ?object
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $message = $folder->query()
                ->setFetchBody(true)
                ->getMessageByUid($uid);
            return $message;
        } finally {
            $client->disconnect();
        }
    }

    public function getAttachment(string $folderPath, int $uid, int $attachmentIndex): ?object
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $message = $folder->query()
                ->setFetchBody(true)
                ->getMessageByUid($uid);

            if (!$message) {
                return null;
            }

            $attachments = $message->getAttachments();
            return $attachments->get($attachmentIndex);
        } finally {
            $client->disconnect();
        }
    }

    public function getMessageCount(string $folderPath): array
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $info = $folder->examine();

            $unread = $folder->query()
                ->unseen()
                ->setFetchBody(false)
                ->count();

            return [
                'total' => (int) $info['exists'],
                'unread' => $unread,
                'uidvalidity' => $info['uidvalidity'],
            ];
        } finally {
            $client->disconnect();
        }
    }

    public function moveMessages(string $folderPath, array $uids, string $destinationPath): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $destFolder = $client->getFolder($destinationPath);

            $messages = $folder->query()
                ->uids($uids)
                ->get();

            foreach ($messages as $message) {
                $message->move($destFolder);
            }

            return true;
        } finally {
            $client->disconnect();
        }
    }

    public function deleteMessages(string $folderPath, array $uids): bool
    {
        $client = $this->getClient();

        try {
            $trashPath = $this->getTrashFolderPath($client);
            if (!$trashPath) {
                $trashPath = $this->getOrCreateTrashFolder($client);
            }

            return $this->moveMessages($folderPath, $uids, $trashPath);
        } finally {
            $client->disconnect();
        }
    }

    public function setFlag(string $folderPath, array $uids, string $flag, bool $value): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $messages = $folder->query()
                ->uids($uids)
                ->get();

            foreach ($messages as $message) {
                if ($value) {
                    $message->setFlag($flag);
                } else {
                    $message->unsetFlag($flag);
                }
            }

            return true;
        } finally {
            $client->disconnect();
        }
    }

    public function createFolder(string $name, string $parentPath = ''): bool
    {
        $client = $this->getClient();

        try {
            $fullPath = $parentPath ? $parentPath . '.' . $name : $name;
            $client->createFolder($fullPath);
            return true;
        } finally {
            $client->disconnect();
        }
    }

    public function renameFolder(string $oldPath, string $newName): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($oldPath);
            $parentPath = $this->getParentPath($oldPath);
            $newPath = $parentPath ? $parentPath . '.' . $newName : $newName;
            $folder->rename($newPath);
            return true;
        } finally {
            $client->disconnect();
        }
    }

    public function deleteFolder(string $folderPath): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $folder->delete();
            return true;
        } finally {
            $client->disconnect();
        }
    }

    public function getCachedFolderInfo(string $folderPath): array
    {
        $cacheKey = "folder:{$folderPath}:" . Auth::id();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($folderPath) {
            return $this->getMessageCount($folderPath);
        });
    }

    public function getCachedFolders(): array
    {
        $cacheKey = 'folders:' . Auth::id();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getFolders();
        });
    }

    public function refreshFolderCache(string $folderPath): void
    {
        $cacheKey = "folder:{$folderPath}:" . Auth::id();
        Cache::forget($cacheKey);

        $cacheKey = 'folders:' . Auth::id();
        Cache::forget($cacheKey);
    }

    private function getParentPath(string $path): string
    {
        $parts = explode('.', $path);
        if (count($parts) <= 1) {
            return '';
        }
        array_pop($parts);
        return implode('.', $parts);
    }

    private function getTrashFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if (in_array('\\Trash', $attributes)) {
                return $folder->path;
            }
        }
        return null;
    }

    private function getOrCreateTrashFolder(Client $client): string
    {
        $trashPath = $this->getTrashFolderPath($client);
        if ($trashPath) {
            return $trashPath;
        }

        $fallbackNames = ['Trash', 'Deleted Items', 'Deleted Messages', 'Bin'];
        foreach ($fallbackNames as $name) {
            try {
                $client->createFolder($name);
                return $name;
            } catch (\Exception) {
                continue;
            }
        }

        return 'Trash';
    }

    /**
     * Append a message to an IMAP folder.
     *
     * @param string $folderPath The folder path
     * @param string $mimeMessage The raw MIME message string
     * @param array $flags IMAP flags to set
     * @param Carbon|null $internalDate Internal date for the message
     * @return string|null The UID of the appended message
     */
    public function appendMessage(string $folderPath, string $mimeMessage, array $flags = [], ?Carbon $internalDate = null): ?string
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            if (!$folder) {
                throw new \Exception("Folder not found: {$folderPath}");
            }

            // Ensure \Seen flag is included by default
            $options = array_merge(['\\Seen'], $flags);
            $date = $internalDate?->format('d-M-Y H:i:s O');

            $result = $folder->appendMessage($mimeMessage, $options, $date);

            // Parse UID from result (webklex returns array with uid/uidvalidity)
            return $this->parseAppendUid($result);
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Append message to Sent folder.
     *
     * @param string $mimeMessage The raw MIME message string
     * @return string|null The UID of the appended message
     */
    public function appendToSent(string $mimeMessage): ?string
    {
        $sentFolder = app(FolderMapper::class)->getSentFolderPath($this->getClient());

        if (!$sentFolder) {
            throw new \Exception('Sent folder not found');
        }

        return $this->appendMessage($sentFolder, $mimeMessage, ['\\Seen'], now());
    }

    /**
     * Append message to Drafts folder.
     *
     * @param string $mimeMessage The raw MIME message string
     * @param string|null $existingUid UID of existing draft to replace
     * @return string|null The UID of the appended message
     */
    public function appendToDrafts(string $mimeMessage, ?string $existingUid = null): ?string
    {
        $client = $this->getClient();

        try {
            $draftsFolder = app(FolderMapper::class)->getDraftsFolderPath($client);

            if (!$draftsFolder) {
                throw new \Exception('Drafts folder not found');
            }

            $folder = $client->getFolder($draftsFolder);

            // If existing UID provided, delete the old draft first
            if ($existingUid) {
                $oldMessage = $folder->query()->getMessageByUid($existingUid);
                if ($oldMessage) {
                    $oldMessage->delete(true); // true = expunge
                }
            }

            return $this->appendMessage($draftsFolder, $mimeMessage, ['\\Draft'], now());
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Delete a message from Drafts folder by UID.
     *
     * @param string $uid The message UID
     * @return bool True if deleted
     */
    public function deleteFromDrafts(string $uid): bool
    {
        $client = $this->getClient();

        try {
            $draftsFolder = app(FolderMapper::class)->getDraftsFolderPath($client);

            if (!$draftsFolder) {
                return false;
            }

            $folder = $client->getFolder($draftsFolder);
            $message = $folder->query()->getMessageByUid($uid);

            if ($message) {
                $message->delete(true); // expunge
                return true;
            }

            return false;
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Delete a message from Sent folder by UID.
     *
     * @param string $uid The message UID
     * @return bool True if deleted
     */
    public function deleteFromSent(string $uid): bool
    {
        $client = $this->getClient();

        try {
            $sentFolder = app(FolderMapper::class)->getSentFolderPath($client);

            if (!$sentFolder) {
                return false;
            }

            $folder = $client->getFolder($sentFolder);
            $message = $folder->query()->getMessageByUid($uid);

            if ($message) {
                $message->delete(true); // expunge
                return true;
            }

            return false;
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Search for recent recipients from Sent and Inbox folders.
     *
     * @param int $limit Maximum number of recipients to return
     * @return array Array of ['email' => ..., 'name' => ..., 'frequency' => ...]
     */
    public function searchRecipients(int $limit = 100): array
    {
        $client = $this->getClient();
        $sinceDate = now()->subDays(30)->format('d-M-Y');
        $emails = [];

        try {
            // Search Sent folder
            $sentFolder = app(FolderMapper::class)->getSentFolderPath($client);
            if ($sentFolder) {
                try {
                    $sentMessages = $client->getFolder($sentFolder)
                        ->query()
                        ->since($sinceDate)
                        ->all()
                        ->get();

                    foreach ($sentMessages as $msg) {
                        foreach ($msg->getTo() as $addr) {
                            $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
                        }
                        foreach ($msg->getCc() as $addr) {
                            $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
                        }
                    }
                } catch (\Exception) {
                    // Ignore folder access errors
                }
            }

            // Search Inbox for From addresses (replies received)
            try {
                $inboxMessages = $client->getFolder('INBOX')
                    ->query()
                    ->since($sinceDate)
                    ->all()
                    ->get();

                foreach ($inboxMessages as $msg) {
                    foreach ($msg->getFrom() as $addr) {
                        $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
                    }
                }
            } catch (\Exception) {
                // Ignore folder access errors
            }

            // Aggregate by email frequency
            $aggregated = collect($emails)
                ->groupBy('email')
                ->map(function ($group) {
                    return [
                        'email' => $group->first()['email'],
                        'name' => $group->first()['name'],
                        'frequency' => $group->count(),
                    ];
                })
                ->values()
                ->sortByDesc('frequency')
                ->take($limit)
                ->values()
                ->toArray();

            return $aggregated;
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Fetch message headers for threading (Message-ID, In-Reply-To, References, Subject, Date).
     * Used to build thread trees for the current page + cross-folder thread roots.
     *
     * @param string $folderPath The IMAP folder path
     * @param array $uids Array of UIDs to fetch headers for
     * @return array<int, object> Array of message objects with header data
     */
    public function getThreadHeaders(string $folderPath, array $uids): array
    {
        if (empty($uids)) {
            return [];
        }

        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            
            // Fetch only headers we need for threading
            $query = $folder->query()
                ->uids($uids)
                ->setFetchBody(false)
                ->setFetchFlags(false)
                ->leaveUnread();

            $messages = $query->get();

            $results = [];
            foreach ($messages as $msg) {
                $headers = $msg->getHeaders();
                
                $results[] = (object) [
                    'uid' => $msg->getUid(),
                    'message_id' => $headers->get('Message-ID')?->getValue() ?? '',
                    'in_reply_to' => $headers->get('In-Reply-To')?->getValue() ?? '',
                    'references' => $headers->get('References')?->getValue() ?? '',
                    'subject' => $headers->get('Subject')?->getValue() ?? '',
                    'date' => $headers->get('Date')?->getValue() ?? '',
                    'from_address' => '',
                    'from_name' => '',
                    'to_address' => '',
                    'is_seen' => $msg->isSeen(),
                    'is_flagged' => $msg->isFlagged(),
                    'has_attachments' => false,
                    'snippet' => '',
                    'folder_path' => $folderPath,
                    'labels' => collect(),
                ];
            }

            return $results;
        } finally {
            $client->disconnect();
        }
    }

    /**
     * Parse UID from webklex appendMessage result.
     *
     * @param array $result
     * @return string|null
     */
    private function parseAppendUid(array $result): ?string
    {
        // webklex returns: ['uid' => 123, 'uidvalidity' => 456] or similar
        return isset($result['uid']) ? (string) $result['uid'] : null;
    }
}