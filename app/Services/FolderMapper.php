<?php

namespace App\Services;

use Webklex\PHPIMAP\Attribute;
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
        '\\All' => 'archive',
        '\\AllMail' => 'archive',
    ];

    private const ROLE_EXACT_NAMES = [
        'inbox' => 'inbox',
        'sent' => 'sent',
        'sent items' => 'sent',
        'sent messages' => 'sent',
        'sent mail' => 'sent',
        'drafts' => 'drafts',
        'draft' => 'drafts',
        'trash' => 'trash',
        'bin' => 'trash',
        'deleted' => 'trash',
        'deleted items' => 'trash',
        'deleted messages' => 'trash',
        'spam' => 'spam',
        'junk' => 'spam',
        'junk email' => 'spam',
        'junk mail' => 'spam',
        'archive' => 'archive',
        'archived' => 'archive',
        'all mail' => 'archive',
        'all' => 'archive',
    ];

    public function mapFolderRole(Folder $folder): ?string
    {
        $attributes = $folder->attributes ?? [];
        if ($attributes instanceof Attribute) {
            $attributes = $attributes->all();
        }
        foreach ((array) $attributes as $attr) {
            $normalizedAttr = '\\'.ucfirst(ltrim(strtolower((string) $attr), '\\'));
            if (isset(self::ROLE_MAP[$normalizedAttr])) {
                return self::ROLE_MAP[$normalizedAttr];
            }
            if (isset(self::ROLE_MAP[$attr])) {
                return self::ROLE_MAP[$attr];
            }
        }

        $path = $folder->path ?? $folder->name ?? '';
        if ($path === 'INBOX' || strcasecmp($path, 'INBOX') === 0) {
            return 'inbox';
        }

        $rawName = strtolower(trim((string) ($folder->name ?? '')));
        if (isset(self::ROLE_EXACT_NAMES[$rawName])) {
            return self::ROLE_EXACT_NAMES[$rawName];
        }

        // Check leaf name if inside standard system namespace ([Gmail], [Google Mail], INBOX, or root)
        $cleanPath = str_replace(['.', '\\'], '/', $path);
        $leaf = strtolower(trim(basename($cleanPath)));
        $parent = dirname($cleanPath);
        $isSystemNamespace = in_array(strtolower($parent), ['.', '', '[gmail]', '[google mail]', 'inbox'], true);

        if ($isSystemNamespace && isset(self::ROLE_EXACT_NAMES[$leaf])) {
            return self::ROLE_EXACT_NAMES[$leaf];
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
     * Normalize and deduplicate discovered IMAP folders for presentation and navigation.
     *
     * Resolves multiple system aliases (e.g. Bin, Deleted Items, and multiple Trash folders)
     * down to a single canonical system folder per role, while preserving genuine user-created mailboxes.
     *
     * @param  array  $folders  Array of mapped folder data arrays
     * @return array Deduplicated and normalized array of folders
     */
    public function normalizeFolders(array $folders): array
    {
        if (empty($folders)) {
            return [];
        }

        $systemRoles = ['inbox', 'drafts', 'sent', 'archive', 'spam', 'trash'];

        $byRole = [];
        $customFolders = [];

        foreach ($folders as $folder) {
            $role = $folder['role'] ?? null;
            if ($role && in_array($role, $systemRoles, true)) {
                $byRole[$role][] = $folder;
            } else {
                $customFolders[] = $folder;
            }
        }

        // Detect if this account heavily uses Gmail namespace
        $hasGmailNamespace = false;
        foreach ($folders as $f) {
            $path = $f['path'] ?? '';
            if (str_starts_with($path, '[Gmail]/') || str_starts_with($path, '[Google Mail]/')) {
                $hasGmailNamespace = true;
                break;
            }
        }

        $normalizedSystem = [];

        foreach ($systemRoles as $role) {
            if (empty($byRole[$role])) {
                continue;
            }

            $candidates = $byRole[$role];

            if (count($candidates) === 1) {
                $canonical = $candidates[0];
                $canonical['name'] = ucfirst($role);
                $normalizedSystem[] = $canonical;

                continue;
            }

            // Multiple folders mapped to the same role: score to find the canonical folder
            $bestCandidate = null;
            $bestScore = -1;

            foreach ($candidates as $cand) {
                $score = 0;
                $candPath = $cand['path'] ?? '';
                $candAttrs = $cand['attributes'] ?? [];
                if ($candAttrs instanceof Attribute) {
                    $candAttrs = $candAttrs->all();
                }

                // 1. Explicit SPECIAL-USE attribute (+100)
                $expectedAttr = match ($role) {
                    'inbox' => '\\Inbox',
                    'sent' => '\\Sent',
                    'drafts' => '\\Drafts',
                    'archive' => '\\Archive',
                    'spam' => '\\Junk',
                    'trash' => '\\Trash',
                    default => null,
                };
                if ($expectedAttr && is_array($candAttrs) && (in_array($expectedAttr, $candAttrs, true) || in_array(strtolower($expectedAttr), array_map('strtolower', $candAttrs), true))) {
                    $score += 100;
                }

                // 2. Namespace consistency (+50)
                if ($hasGmailNamespace && (str_starts_with($candPath, '[Gmail]/') || str_starts_with($candPath, '[Google Mail]/'))) {
                    $score += 50;
                } elseif (! $hasGmailNamespace && ! str_contains($candPath, '/')) {
                    $score += 40;
                }

                // 3. Exact standard name match (+30)
                $leaf = strtolower(trim(basename(str_replace(['.', '\\'], '/', $candPath))));
                if ($leaf === $role) {
                    $score += 30;
                } elseif ($role === 'trash' && $leaf === 'bin' && ! $hasGmailNamespace) {
                    $score += 20;
                } elseif ($role === 'trash' && ($leaf === 'deleted items' || $leaf === 'deleted')) {
                    $score += 10;
                }

                // 4. Folder actually has message content (+15)
                $count = (int) ($cand['total'] ?? ($cand['total_count'] ?? 0));
                if ($count > 0) {
                    $score += 15;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestCandidate = $cand;
                }
            }

            if ($bestCandidate) {
                $bestCandidate['name'] = ucfirst($role);
                $normalizedSystem[] = $bestCandidate;
            }
        }

        $seenPaths = [];
        foreach ($normalizedSystem as $sys) {
            $p = strtolower(trim($sys['path'] ?? ''));
            if ($p !== '') {
                $seenPaths[$p] = true;
            }
        }

        $dedupedCustom = [];
        foreach ($customFolders as $cf) {
            $p = strtolower(trim($cf['path'] ?? ''));
            if ($p !== '' && isset($seenPaths[$p])) {
                continue;
            }
            if ($p !== '') {
                $seenPaths[$p] = true;
            }
            $dedupedCustom[] = $cf;
        }

        // Return combined list: normalized system folders + custom folders
        return array_values(array_merge($normalizedSystem, $dedupedCustom));
    }

    /**
     * Get the Sent folder path using SPECIAL-USE or heuristic.
     */
    public function getSentFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if ($attributes instanceof Attribute) {
                $attributes = $attributes->all();
            }
            $attrs = array_map('strtolower', (array) $attributes);
            if (in_array('\\sent', $attrs, true)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Sent', 'Sent Messages', 'Sent Items', 'Sent Mail'];
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
            if ($attributes instanceof Attribute) {
                $attributes = $attributes->all();
            }
            $attrs = array_map('strtolower', (array) $attributes);
            if (in_array('\\drafts', $attrs, true)) {
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
            if ($attributes instanceof Attribute) {
                $attributes = $attributes->all();
            }
            $attrs = array_map('strtolower', (array) $attributes);
            if (in_array('\\archive', $attrs, true) || in_array('\\all', $attrs, true)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Archive', 'Archived', 'All Mail'];
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

    /**
     * Get the Trash folder path using SPECIAL-USE or heuristic.
     */
    public function getTrashFolderPath(Client $client): ?string
    {
        $folders = $client->getFolders();
        foreach ($folders as $folder) {
            $attributes = $folder->attributes ?? [];
            if ($attributes instanceof Attribute) {
                $attributes = $attributes->all();
            }
            $attrs = array_map('strtolower', (array) $attributes);
            if (in_array('\\trash', $attrs, true)) {
                return $folder->path;
            }
        }

        // Fallback to heuristic names
        $fallbackNames = ['Trash', 'Deleted Items', 'Deleted Messages', 'Bin'];
        foreach ($fallbackNames as $name) {
            foreach ($folders as $folder) {
                if (strcasecmp($folder->name, $name) === 0) {
                    return $folder->path;
                }
            }
        }

        // Fallback to role heuristics
        foreach ($folders as $folder) {
            if ($this->mapFolderRole($folder) === 'trash') {
                return $folder->path;
            }
        }

        return null;
    }
}
