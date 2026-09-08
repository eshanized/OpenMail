<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';

    protected $description = 'Prune audit logs older than 90 days';

    public function handle(): int
    {
        $deleted = AuditLog::where('created_at', '<', now()->subDays(90))->delete();

        $this->info("Pruned {$deleted} audit log entries older than 90 days.");

        return self::SUCCESS;
    }
}
