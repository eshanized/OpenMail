<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\SystemRequirementsChecker;
use ReflectionMethod;
use Tests\TestCase;

class SystemRequirementsCheckerTest extends TestCase
{
    private SystemRequirementsChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new SystemRequirementsChecker;
    }

    public function test_check_returns_all_required_and_recommended_sections(): void
    {
        $result = $this->checker->check();

        $this->assertSame(['php_version', 'extensions', 'writable', 'database', 'recommended'], array_keys($result));
        $this->assertNotEmpty($result['extensions']);
        $this->assertNotEmpty($result['writable']);
        $this->assertNotEmpty($result['recommended']);
        foreach ($result['extensions'] as $extension) {
            $this->assertArrayHasKey('passed', $extension);
            $this->assertArrayHasKey('fix', $extension);
        }
    }

    public function test_all_required_passed_ignores_recommended_warnings(): void
    {
        $result = [
            'php_version' => ['passed' => true],
            'database' => ['passed' => true],
            'extensions' => [['passed' => true], ['passed' => true]],
            'writable' => [['passed' => true]],
            'recommended' => [['passed' => false]],
        ];

        $this->assertTrue($this->checker->allRequiredPassed($result));
    }

    public function test_all_required_passed_fails_for_each_required_category(): void
    {
        $valid = [
            'php_version' => ['passed' => true],
            'database' => ['passed' => true],
            'extensions' => [['passed' => true]],
            'writable' => [['passed' => true]],
        ];

        foreach (['php_version', 'database'] as $category) {
            $invalid = $valid;
            $invalid[$category]['passed'] = false;
            $this->assertFalse($this->checker->allRequiredPassed($invalid));
        }

        $invalidExtension = $valid;
        $invalidExtension['extensions'][] = ['passed' => false];
        $this->assertFalse($this->checker->allRequiredPassed($invalidExtension));

        $invalidPath = $valid;
        $invalidPath['writable'][] = ['passed' => false];
        $this->assertFalse($this->checker->allRequiredPassed($invalidPath));
    }

    public function test_ini_size_parser_handles_gigabytes_megabytes_kilobytes_bytes_and_unlimited(): void
    {
        $method = new ReflectionMethod($this->checker, 'parseIniBytes');
        $this->assertSame(2 * 1024 * 1024 * 1024, $method->invoke($this->checker, '2G'));
        $this->assertSame(256 * 1024 * 1024, $method->invoke($this->checker, '256M'));
        $this->assertSame(128 * 1024, $method->invoke($this->checker, '128K'));
        $this->assertSame(4096, $method->invoke($this->checker, '4096'));
        $this->assertSame(-1, $method->invoke($this->checker, '-1'));
        $this->assertSame(64 * 1024 * 1024, $method->invoke($this->checker, ' 64m '));
    }

    public function test_extension_remediation_messages_cover_known_and_unknown_extensions(): void
    {
        $method = new ReflectionMethod($this->checker, 'extensionFix');

        $this->assertStringContainsString('pdo_mysql', $method->invoke($this->checker, 'pdo_mysql'));
        $this->assertStringContainsString('mbstring', $method->invoke($this->checker, 'mbstring'));
        $this->assertStringContainsString('openssl', $method->invoke($this->checker, 'openssl'));
        $this->assertStringContainsString('curl', $method->invoke($this->checker, 'curl'));
        $this->assertStringContainsString('xml', $method->invoke($this->checker, 'xml'));
        $this->assertStringContainsString('fileinfo', $method->invoke($this->checker, 'fileinfo'));
        $this->assertStringContainsString('imaginary_extension', $method->invoke($this->checker, 'imaginary_extension'));
    }
}
