<?php

declare(strict_types=1);

namespace App\Install;

enum InstallationState: string
{
    case NotInstalled = 'not_installed';
    case Installing = 'installing';
    case Installed = 'installed';
    case Failed = 'failed';

    public function isInstalled(): bool
    {
        return $this === self::Installed;
    }

    public function canInstall(): bool
    {
        return in_array($this, [self::NotInstalled, self::Failed]);
    }

    public function label(): string
    {
        return match ($this) {
            self::NotInstalled => 'Not Installed',
            self::Installing => 'Installing…',
            self::Installed => 'Installed',
            self::Failed => 'Installation Failed',
        };
    }
}
