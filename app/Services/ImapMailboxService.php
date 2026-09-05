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
}