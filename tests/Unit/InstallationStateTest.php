<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Install\InstallationState;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class InstallationStateTest extends TestCase
{
    public static function states(): array
    {
        return [
            [InstallationState::NotInstalled, false, true, 'Not Installed'],
            [InstallationState::Installing, false, false, 'Installing…'],
            [InstallationState::Installed, true, false, 'Installed'],
            [InstallationState::Failed, false, true, 'Installation Failed'],
        ];
    }

    #[DataProvider('states')]
    public function test_state_helpers_return_expected_results(
        InstallationState $state,
        bool $installed,
        bool $canInstall,
        string $label
    ): void {
        $this->assertSame($installed, $state->isInstalled());
        $this->assertSame($canInstall, $state->canInstall());
        $this->assertSame($label, $state->label());
    }
}
