<?php

namespace App\Console\Commands;

use App\Models\PendingSend;
use App\Models\User;
use App\Services\ComposerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class ProcessPendingSends extends Command
{
    protected $signature = 'pending-sends:process';

    protected $description = 'Process pending email sends that are due';

    public function handle(): int
    {
        $pending = PendingSend::where('status', 'pending')
            ->where('send_at', '<=', now())
            ->limit(50)
            ->get();

        if ($pending->isEmpty()) {
            $this->info('No pending sends to process.');

            return 0;
        }

        $this->info("Processing {$pending->count()} pending send(s)...");

        foreach ($pending as $send) {
            // message_json is already decoded to array via model cast
            $data = $send->message_json;

            try {
                $user = User::find($send->user_id);

                // Resend via SMTP using the pre-built MIME message
                Mail::raw($send->mime_message, function ($message) use ($data, $send, $user) {
                    if ($user) {
                        $message->from($user->email, $user->name ?? '');
                    }
                    $message->to($data['to'] ?? '')
                        ->cc($data['cc'] ?? '')
                        ->bcc($data['bcc'] ?? '')
                        ->subject($data['subject'] ?? '(no subject)');

                    // Replace Symfony's generated MIME with our pre-built one
                    $message->using(function ($symfonyMessage) use ($send, $data) {
                        try {
                            $composerService = app(ComposerService::class);
                            $built = $composerService->buildMimeMessage($data, (string) $send->user_id);
                            if (is_object($symfonyMessage) && method_exists($symfonyMessage, 'getHeaders')) {
                                foreach ($built->getHeaders()->all() as $header) {
                                    $symfonyMessage->getHeaders()->add($header);
                                }
                            }
                            if (is_object($symfonyMessage) && method_exists($symfonyMessage, 'setBody') && $built->getBody()) {
                                $symfonyMessage->setBody($built->getBody());
                            }
                        } catch (\Throwable) {
                            // Fallback if rebuild fails
                        }
                    });
                });

                $send->update(['status' => 'sent']);
                $this->info("Send {$send->id} processed successfully.");

            } catch (\Exception $e) {
                $send->increment('retry_count');
                $this->warn("Send {$send->id} failed: {$e->getMessage()} (attempt {$send->retry_count})");

                if ($send->retry_count >= 3) {
                    $send->update(['status' => 'failed']);
                    $this->error("Send {$send->id} marked as failed after 3 retries.");
                    // TODO: Notify user via notification system
                } else {
                    // Reschedule with exponential backoff
                    $backoffMinutes = pow(2, $send->retry_count);
                    $send->update(['send_at' => now()->addMinutes($backoffMinutes)]);
                    $this->info("Send {$send->id} rescheduled in {$backoffMinutes} minute(s).");
                }
            }
        }

        return 0;
    }
}
