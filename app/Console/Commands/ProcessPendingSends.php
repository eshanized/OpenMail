<?php

namespace App\Console\Commands;

use App\Models\PendingSend;
use App\Services\ComposerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessPendingSends extends Command
{
    protected $signature = 'pending-sends:process {--batch=50 : Maximum number of records to process in one run} {--lease=300 : Worker lease time in seconds}';

    protected $description = 'Process pending and retry-waiting email sends with atomic concurrency-safe claims';

    public function handle(ComposerService $composerService): int
    {
        $batchSize = max(1, min(100, (int) $this->option('batch')));
        $leaseSeconds = max(60, (int) $this->option('lease'));
        $workerToken = (string) Str::uuid();

        // 1. Recover stale claimed records whose worker process died or timed out
        $this->recoverStaleClaims($leaseSeconds);

        // 2. Select IDs of due pending sends
        $dueIds = PendingSend::due()
            ->orderBy('send_at', 'asc')
            ->limit($batchSize)
            ->pluck('id')
            ->toArray();

        if (empty($dueIds)) {
            $this->info('No pending sends to process.');

            return 0;
        }

        $this->info(sprintf('Found %d candidate pending send(s). Claiming with token %s...', count($dueIds), $workerToken));

        $claimedCount = 0;
        $sentCount = 0;
        $failedCount = 0;

        foreach ($dueIds as $id) {
            // 3. Atomically claim each record individually using compare-and-set
            $claimed = DB::table('pending_sends')
                ->where('id', $id)
                ->whereIn('status', [PendingSend::STATUS_PENDING, PendingSend::STATUS_RETRY_WAITING])
                ->where('send_at', '<=', now())
                ->update([
                    'status' => PendingSend::STATUS_CLAIMED,
                    'claim_token' => $workerToken,
                    'claimed_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($claimed === 0) {
                // Record was claimed by another worker or cancelled by user; skip safely
                continue;
            }

            $claimedCount++;
            $send = PendingSend::find($id);
            if (! $send) {
                continue;
            }

            // 4. Mark processing and deliver
            $this->line("Processing send #{$send->id} (attempt ".($send->retry_count + 1).')...');

            try {
                $result = $composerService->deliverPendingSend($send, $workerToken);

                if (! empty($result['success'])) {
                    $sentCount++;
                    $this->info("Send #{$send->id} delivered successfully.");
                } elseif (! empty($result['uncertain'])) {
                    $this->warn("Send #{$send->id} marked as UNCERTAIN delivery (requires reconciliation).");
                } elseif (! empty($result['retry_scheduled'])) {
                    $this->warn("Send #{$send->id} transport error, rescheduled for retry.");
                } else {
                    $failedCount++;
                    $this->error("Send #{$send->id} permanently failed: ".($result['error'] ?? 'Unknown error'));
                }
            } catch (\Throwable $e) {
                Log::error("Unhandled exception processing send #{$send->id}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $send->recordFailure($e->getMessage(), PendingSend::CATEGORY_TRANSPORT, true);
                $failedCount++;
            }
        }

        $this->info("Batch complete: {$claimedCount} claimed, {$sentCount} delivered, {$failedCount} failed.");

        return 0;
    }

    /**
     * Recover records whose worker lease has expired without completion.
     */
    protected function recoverStaleClaims(int $leaseSeconds): void
    {
        $recovered = DB::table('pending_sends')
            ->whereIn('status', [PendingSend::STATUS_CLAIMED, PendingSend::STATUS_PROCESSING])
            ->where('claimed_at', '<=', now()->subSeconds($leaseSeconds))
            ->where('smtp_accepted', false)
            ->update([
                'status' => PendingSend::STATUS_RETRY_WAITING,
                'claim_token' => null,
                'claimed_at' => null,
                'updated_at' => now(),
            ]);

        if ($recovered > 0) {
            $this->warn("Recovered {$recovered} stale pending send lease(s).");
            Log::warning("Recovered {$recovered} abandoned pending send records whose worker lease expired.");
        }
    }
}
