<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PruneAuditLogsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_only_audit_logs_older_than_ninety_days(): void
    {
        $old = AuditLog::create([
            'event_type' => 'security.old',
            'description' => 'old event',
            'created_at' => now()->subDays(91),
        ]);
        $recent = AuditLog::create([
            'event_type' => 'security.recent',
            'description' => 'recent event',
            'created_at' => now()->subDays(89),
        ]);

        DB::table('audit_logs')->where('id', $old->id)->update(['created_at' => now()->subDays(91)]);
        DB::table('audit_logs')->where('id', $recent->id)->update(['created_at' => now()->subDays(89)]);

        $exitCode = Artisan::call('audit:prune');

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseMissing('audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recent->id]);
        $this->assertStringContainsString('Pruned 1 audit log entries', Artisan::output());
    }

    public function test_command_succeeds_when_there_are_no_expired_logs(): void
    {
        AuditLog::create([
            'event_type' => 'security.recent',
            'description' => 'recent event',
            'created_at' => now()->subDays(2),
        ]);

        $this->assertSame(0, Artisan::call('audit:prune'));
        $this->assertStringContainsString('Pruned 0 audit log entries', Artisan::output());
        $this->assertDatabaseCount('audit_logs', 1);
    }
}
