<?php

namespace App\Services;

use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;

class FolderMapper
{
    private const ROLE_MAP = [
        '\\Inbox' => 'inbox',
        '\\Sent' => 'sent',
        '\\Drafts' => 'drafts',
        '\\Trash' => 'trash',
        '\\Junk' => 'spam',
        '\\Archive' => 'archive',
    ];

    private const NAME_HEURISTICS = [
        'inbox' => 'inbox',
        'sent' => 'sent',
        'drafts' => 'drafts',
        'trash' => 'trash',
        'deleted' => 'trash',
        'spam' => 'spam',
        'junk' => 'spam',
        'archive' => 'archive',
        'archiv' => 'archive',
    ];

    public function mapFolderRole(Folder $folder): ?string
    {
        $attributes = $folder->attributes ?? [];
        foreach ($attributes as $attr) {
            if (isset(self::ROLE_MAP[$attr])) {
                return self::ROLE_MAP[$attr];
            }
        }

        $name = strtolower($folder->name);
        foreach (self::NAME_HEURISTICS as $pattern => $role) {
            if (str_contains($name, $pattern)) {
                return $role;
            }
        }

        if ($folder->path === 'INBOX') {
            return 'inbox';
        }

        return null;
    }

    public function mapFolderName(Folder $folder): string
    {
        $role = $this->mapFolderRole($folder);
        if ($role) {
            return ucfirst($role);
        }

        return $folder->name;
    }

    /**
     * Get the Sent folder path using SPECIAL-USE or heuristic.
     */
    public function getSentFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if (in_array('\\Sent', $attributes)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Sent', 'Sent Messages', 'Sent Items'];
        foreach ($fallbackNames as $name) {
            foreach ($folders as $folder) {
                if (strcasecmp($folder->name, $name) === 0) {
                    return $folder->path;
                }
            }
        }

        return null;
    }

    /**
     * Get the Drafts folder path using SPECIAL-USE or heuristic.
     */
    public function getDraftsFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if (in_array('\\Drafts', $attributes)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Drafts', 'Draft'];
        foreach ($fallbackNames as $name) {
            foreach ($folders as $folder) {
                if (strcasecmp($folder->name, $name) === 0) {
                    return $folder->path;
                }
            }
        }

        return null;
    }

    /**
     * Get the Archive folder path using SPECIAL-USE or heuristic.
     * Falls back to creating "Archive" folder if not found.
     */
    public function getArchiveFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if (in_array('\\Archive', $attributes)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Archive', 'Archived', 'Archive'];
        foreach ($fallbackNames as $name) {
            foreach ($folders as $folder) {
                if (strcasecmp($folder->name, $name) === 0) {
                    return $folder->path;
                }
            }
        }

        // Try to create the Archive folder
        try {
            $client->createFolder('Archive');

            return 'Archive';
        } catch (\Exception) {
            // Folder may already exist or creation failed
        }

        return null;
    }
}
