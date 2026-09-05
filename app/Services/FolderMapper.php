<?php

namespace App\Services;

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
}