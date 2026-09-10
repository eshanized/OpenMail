<?php

namespace App\Console\Commands;

use App\Models\PendingSend;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;

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
                // Resend via SMTP using the pre-built MIME message
                Mail::raw($send->mime_message, function ($message) use ($data) {
                    $message->to($data['to'] ?? '')
                        ->cc($data['cc'] ?? '')
                        ->bcc($data['bcc'] ?? '')
                        ->subject($data['subject'] ?? '(no subject)');

                    // Replace Symfony's generated MIME with our pre-built one
                    $message->using(function (Email $symfonyMessage) use ($send) {
                        $mimeMessage = new Email;
                        $mimeMessage = $mimeMessage->fromString($send->mime_message);
                        foreach ($mimeMessage->getHeaders()->all() as $header) {
                            $symfonyMessage->getHeaders()->add($header);
                        }
                        $symfonyMessage->setBody($mimeMessage->getBody());
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
