<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Webklex\PHPIMAP\Address;
use Webklex\PHPIMAP\Attribute;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Config;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

class ImapMailboxService
{
    protected ?Client $activeClient = null;

    private function getClient(): Client
    {
        if ($this->activeClient !== null) {
            try {
                if ($this->activeClient->isConnected()) {
                    return $this->activeClient;
                }
            } catch (\Throwable) {
                $this->activeClient = null;
            }
        }

        $config = Config::make();
        $config->set('accounts.default.host', config('openmail.imap.host'));
        $config->set('accounts.default.port', (int) config('openmail.imap.port', 993));
        $config->set('accounts.default.encryption', config('openmail.imap.encryption', 'ssl'));
        $config->set('accounts.default.username', Auth::user()->email);
        $config->set('accounts.default.password', Crypt::decrypt(session('openmail:imap_password')));
        $config->set('accounts.default.protocol', 'imap');
        $config->set('accounts.default.timeout', 10);
        $config->set('accounts.default.validate_cert', true);

        $client = new Client($config);
        $client->connect();
        $this->activeClient = $client;

        return $client;
    }

    public function disconnect(): void
    {
        if ($this->activeClient !== null) {
            try {
                if ($this->activeClient->isConnected()) {
                    $this->activeClient->disconnect();
                }
            } catch (\Throwable) {
                // Ignore disconnect errors during termination
            }
            $this->activeClient = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
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

                $totalCount = 0;
                $unreadCount = 0;
                $uidvalidity = null;

                try {
                    $status = $folder->status();
                    $totalCount = (int) ($status['messages'] ?? 0);
                    $unreadCount = (int) ($status['unseen'] ?? 0);
                    $uidvalidity = $status['uidvalidity'] ?? null;
                } catch (\Throwable) {
                    try {
                        $info = $folder->examine();
                        $totalCount = (int) ($info['exists'] ?? 0);
                        $uidvalidity = $info['uidvalidity'] ?? null;
                    } catch (\Throwable) {
                        // Keep defaults
                    }
                }

                $result[] = [
                    'path' => $folder->path,
                    'name' => $name,
                    'role' => $role,
                    'total' => $totalCount,
                    'total_count' => $totalCount,
                    'unread' => $unreadCount,
                    'unread_count' => $unreadCount,
                    'uidvalidity' => $uidvalidity,
                    'has_children' => $folder->hasChildren(),
                    'parent_path' => $this->getParentPath($folder->path),
                ];
            }

            $rolePriority = [
                'inbox' => 10,
                'drafts' => 20,
                'sent' => 30,
                'archive' => 40,
                'spam' => 50,
                'trash' => 60,
            ];

            usort($result, function ($a, $b) use ($rolePriority) {
                $roleA = $a['role'] ?? null;
                $roleB = $b['role'] ?? null;

                $priorityA = $roleA && isset($rolePriority[$roleA]) ? $rolePriority[$roleA] : 100;
                $priorityB = $roleB && isset($rolePriority[$roleB]) ? $rolePriority[$roleB] : 100;

                if ($priorityA !== $priorityB) {
                    return $priorityA <=> $priorityB;
                }

                return strcasecmp($a['name'] ?? '', $b['name'] ?? '');
            });

            return $result;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function getMessages(string $folderPath, string $sortBy = 'date', string $sortDir = 'desc', int $page = 1, int $perPage = 25): LengthAwarePaginator
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            if (! $folder) {
                return new LengthAwarePaginator([], 0, $perPage, $page, [
                    'path' => request()->url(),
                    'pageName' => 'messages-page',
                ]);
            }

            $query = $folder->query()
                ->all()
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
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function getMessage(string $folderPath, int $uid): ?object
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            if (! $folder) {
                return null;
            }
            $message = $folder->query()->getMessageByUid($uid);

            return $message;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function getMessageWithBody(string $folderPath, int $uid): ?object
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            if (! $folder) {
                return null;
            }
            $message = $folder->query()
                ->setFetchBody(true)
                ->getMessageByUid($uid);

            return $message;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
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

            if (! $message) {
                return null;
            }

            $attachments = $message->getAttachments();

            return $attachments->get($attachmentIndex);
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
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
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function moveMessages(string $folderPath, array $uids, string $destinationPath): bool
    {
        if (empty($uids)) {
            return true;
        }

        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $destFolder = $client->getFolder($destinationPath);
            $targetPath = $destFolder ? $destFolder->path : $destinationPath;

            $messages = $folder->query()
                ->whereUidIn($uids)
                ->get();

            foreach ($messages as $message) {
                $message->move($targetPath);
            }

            $this->refreshFolderCache($folderPath);
            $this->refreshFolderCache($destinationPath);

            return true;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function deleteMessages(string $folderPath, array $uids): bool
    {
        if (empty($uids)) {
            return true;
        }

        $client = $this->getClient();

        try {
            $trashPath = $this->getTrashFolderPath($client);
            if (! $trashPath) {
                $trashPath = $this->getOrCreateTrashFolder($client);
            }

            // If already inside the trash folder, permanently delete messages
            if ($trashPath && strcasecmp($folderPath, $trashPath) === 0) {
                $folder = $client->getFolder($folderPath);
                $messages = $folder->query()
                    ->whereUidIn($uids)
                    ->get();

                foreach ($messages as $message) {
                    $message->delete(expunge: true);
                }

                $this->refreshFolderCache($folderPath);

                return true;
            }

            return $this->moveMessages($folderPath, $uids, $trashPath);
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function setFlag(string $folderPath, array $uids, string $flag, bool $value): bool
    {
        if (empty($uids)) {
            return true;
        }

        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $messages = $folder->query()
                ->whereUidIn($uids)
                ->get();

            // webklex Message::setFlag/unsetFlag unconditionally prepends a backslash ("\\"),
            // so we strip any leading backslash to prevent generating an invalid IMAP flag like \\SEEN.
            $cleanFlag = ltrim(trim($flag), '\\');

            foreach ($messages as $message) {
                if ($value) {
                    $message->setFlag($cleanFlag);
                } else {
                    $message->unsetFlag($cleanFlag);
                }
            }

            return true;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function createFolder(string $name, string $parentPath = ''): bool
    {
        $client = $this->getClient();

        try {
            $fullPath = $parentPath ? $parentPath.'.'.$name : $name;
            $client->createFolder($fullPath);

            return true;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function renameFolder(string $oldPath, string $newName): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($oldPath);
            $parentPath = $this->getParentPath($oldPath);
            $newPath = $parentPath ? $parentPath.'.'.$newName : $newName;
            $folder->rename($newPath);

            return true;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function deleteFolder(string $folderPath): bool
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            $folder->delete();

            return true;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    public function getCachedFolderInfo(string $folderPath): array
    {
        $cacheKey = "folder:{$folderPath}:".Auth::id();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($folderPath) {
            return $this->getMessageCount($folderPath);
        });
    }

    public function getCachedFolders(): array
    {
        $cacheKey = 'folders:'.Auth::id();

        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return $this->getFolders();
        });
    }

    public function refreshFolderCache(string $folderPath): void
    {
        $cacheKey = "folder:{$folderPath}:".Auth::id();
        Cache::forget($cacheKey);

        $cacheKey = 'folders:'.Auth::id();
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
     * @param  string  $folderPath  The folder path
     * @param  string  $mimeMessage  The raw MIME message string
     * @param  array  $flags  IMAP flags to set
     * @param  Carbon|null  $internalDate  Internal date for the message
     * @return string|null The UID of the appended message
     */
    public function appendMessage(string $folderPath, string $mimeMessage, array $flags = [], ?Carbon $internalDate = null): ?string
    {
        $client = $this->getClient();

        try {
            $folder = $client->getFolder($folderPath);
            if (! $folder) {
                throw new \Exception("Folder not found: {$folderPath}");
            }

            // Ensure \Seen flag is included by default
            $options = array_merge(['\\Seen'], $flags);
            $date = $internalDate?->format('d-M-Y H:i:s O');

            $result = $folder->appendMessage($mimeMessage, $options, $date);

            // Parse UID from result (webklex returns array with uid/uidvalidity)
            return $this->parseAppendUid($result);
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Append message to Sent folder.
     *
     * @param  string  $mimeMessage  The raw MIME message string
     * @return string|null The UID of the appended message
     */
    public function appendToSent(string $mimeMessage): ?string
    {
        $sentFolder = app(FolderMapper::class)->getSentFolderPath($this->getClient());

        if (! $sentFolder) {
            throw new \Exception('Sent folder not found');
        }

        return $this->appendMessage($sentFolder, $mimeMessage, ['\\Seen'], now());
    }

    /**
     * Append message to Drafts folder.
     *
     * @param  string  $mimeMessage  The raw MIME message string
     * @param  string|null  $existingUid  UID of existing draft to replace
     * @return string|null The UID of the appended message
     */
    public function appendToDrafts(string $mimeMessage, ?string $existingUid = null): ?string
    {
        $client = $this->getClient();

        try {
            $draftsFolder = app(FolderMapper::class)->getDraftsFolderPath($client);

            if (! $draftsFolder) {
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
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Delete a message from Drafts folder by UID.
     *
     * @param  string  $uid  The message UID
     * @return bool True if deleted
     */
    public function deleteFromDrafts(string $uid): bool
    {
        $client = $this->getClient();

        try {
            $draftsFolder = app(FolderMapper::class)->getDraftsFolderPath($client);

            if (! $draftsFolder) {
                return false;
            }

            $folder = $client->getFolder($draftsFolder);
            $message = $folder->query()->getMessageByUid($uid);

            if ($message) {
                $message->delete(true); // expunge

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Delete a message from Sent folder by UID.
     *
     * @param  string  $uid  The message UID
     * @return bool True if deleted
     */
    public function deleteFromSent(string $uid): bool
    {
        $client = $this->getClient();

        try {
            $sentFolder = app(FolderMapper::class)->getSentFolderPath($client);

            if (! $sentFolder) {
                return false;
            }

            $folder = $client->getFolder($sentFolder);
            $message = $folder->query()->getMessageByUid($uid);

            if ($message) {
                $message->delete(true); // expunge

                return true;
            }

            return false;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Search for recent recipients from Sent and Inbox folders.
     *
     * @param  int  $limit  Maximum number of recipients to return
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
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Parse UID from webklex appendMessage result.
     */
    private function parseAppendUid(array $result): ?string
    {
        // webklex returns: ['uid' => 123, 'uidvalidity' => 456] or similar
        return isset($result['uid']) ? (string) $result['uid'] : null;
    }

    /**
     * Fetch message headers for threading (Message-ID, In-Reply-To, References, Subject, Date).
     * Used to build thread trees for the current page + cross-folder thread roots.
     *
     * @param  string  $folderPath  The IMAP folder path
     * @param  array  $uids  Array of UIDs to fetch headers for
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
            if (! $folder) {
                return [];
            }

            // Fetch only headers we need for threading
            $query = $folder->query()
                ->whereUidIn($uids)
                ->setFetchBody(false)
                ->setFetchFlags(false)
                ->leaveUnread();

            $messages = $query->get();

            $results = [];
            foreach ($messages as $msg) {
                $header = $msg->getHeader();

                $fromObj = null;
                if (method_exists($msg, 'getFrom')) {
                    $fromAttr = $msg->getFrom();
                    if ($fromAttr instanceof Attribute) {
                        $fromObj = $fromAttr->first();
                    }
                }
                if (! $fromObj && $header) {
                    $fromAttr = $header->get('from');
                    if ($fromAttr instanceof Attribute) {
                        $fromObj = $fromAttr->first();
                    }
                }
                if (! $fromObj && isset($msg->from)) {
                    $fromVal = $msg->from;
                    if ($fromVal instanceof Attribute) {
                        $fromObj = $fromVal->first();
                    } elseif (is_iterable($fromVal)) {
                        $fromObj = collect($fromVal)->first();
                    } else {
                        $fromObj = $fromVal;
                    }
                }

                $fromAddress = '';
                $fromName = '';
                if ($fromObj) {
                    if ($fromObj instanceof Address) {
                        $fromAddress = $fromObj->mail ?? '';
                        $fromName = $fromObj->personal ?? '';
                    } elseif (is_object($fromObj)) {
                        $fromAddress = $fromObj->mail ?? (isset($fromObj->mailbox, $fromObj->host) && $fromObj->mailbox && $fromObj->host ? $fromObj->mailbox.'@'.$fromObj->host : '');
                        $fromName = $fromObj->personal ?? '';
                    } elseif (is_string($fromObj)) {
                        $fromAddress = $fromObj;
                    }
                }

                $fromDisplay = ! empty($fromName) ? $fromName : (! empty($fromAddress) ? $fromAddress : 'Unknown');
                $to = $msg->to ? (is_iterable($msg->to) ? collect($msg->to)->first() : $msg->to) : null;

                $msgId = (string) ($msg->message_id ?? $header?->get('message_id') ?? '');
                if (empty($msgId)) {
                    $msgId = 'uid-'.$msg->getUid().'@openmail.local';
                }

                $rawDate = (string) ($msg->date ?? $header?->get('date') ?? '');

                $results[] = (object) [
                    'uid' => $msg->getUid(),
                    'message_id' => $msgId,
                    'in_reply_to' => (string) ($msg->in_reply_to ?? $header?->get('in_reply_to') ?? ''),
                    'references' => (string) ($msg->references ?? $header?->get('references') ?? ''),
                    'subject' => (string) ($msg->subject ?? $header?->get('subject') ?? '(no subject)'),
                    'date' => $rawDate,
                    'from_address' => $fromAddress,
                    'from_name' => $fromName,
                    'from_display' => $fromDisplay,
                    'to_address' => $to?->mail ?? (string) ($msg->to ?? ''),
                    'is_seen' => (bool) ($msg->getFlags()?->has('seen') ?? false),
                    'is_flagged' => (bool) ($msg->getFlags()?->has('flagged') ?? false),
                    'has_attachments' => method_exists($msg, 'hasAttachments') ? $msg->hasAttachments() : false,
                    'snippet' => '',
                    'folder_path' => $folderPath,
                    'labels' => collect(),
                ];
            }

            return $results;
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Get or create the Archive folder path.
     *
     * @return string|null The Archive folder path
     */
    public function getOrCreateArchiveFolder(): ?string
    {
        $client = $this->getClient();

        try {
            $mapper = app(FolderMapper::class);

            return $mapper->getArchiveFolderPath($client);
        } catch (\Throwable $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Move a message to the Archive folder.
     *
     * @param  int  $messageUid  The message UID
     * @param  string  $sourceFolder  The source folder path
     * @return bool True if successful
     */
    public function moveMessageToArchive(int $messageUid, string $sourceFolder): bool
    {
        $archivePath = $this->getOrCreateArchiveFolder();

        if (! $archivePath) {
            throw new \Exception('Could not find or create Archive folder');
        }

        return $this->moveMessages($sourceFolder, [$messageUid], $archivePath);
    }

    /**
     * Move a message from Archive back to a source folder (for undo).
     *
     * @param  int  $messageUid  The message UID
     * @param  string  $sourceFolder  The folder to move back to (e.g., INBOX)
     * @return bool True if successful
     */
    public function moveMessageFromArchive(int $messageUid, string $sourceFolder): bool
    {
        $archivePath = $this->getOrCreateArchiveFolder();

        if (! $archivePath) {
            throw new \Exception('Could not find Archive folder');
        }

        return $this->moveMessages($archivePath, [$messageUid], $sourceFolder);
    }
}
