<div class="space-y-4">
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
        <div class="rounded-xl border border-border bg-surface-raised p-3">
            <p class="text-[11px] text-ink-tertiary">Total records</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ array_sum($counts) }}</p>
        </div>
        <div class="rounded-xl border border-border bg-surface-raised p-3">
            <p class="text-[11px] text-ink-tertiary">Sent</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ $counts[\App\Models\PendingSend::STATUS_SENT] ?? 0 }}</p>
        </div>
        <div class="rounded-xl border border-border bg-surface-raised p-3">
            <p class="text-[11px] text-ink-tertiary">Needs attention</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ ($counts[\App\Models\PendingSend::STATUS_FAILED] ?? 0) + ($counts[\App\Models\PendingSend::STATUS_UNCERTAIN] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-border bg-surface-raised p-3">
            <p class="text-[11px] text-ink-tertiary">Waiting / queued</p>
            <p class="mt-1 text-xl font-semibold tabular-nums text-ink">{{ ($counts[\App\Models\PendingSend::STATUS_PENDING] ?? 0) + ($counts[\App\Models\PendingSend::STATUS_CLAIMED] ?? 0) + ($counts[\App\Models\PendingSend::STATUS_PROCESSING] ?? 0) + ($counts[\App\Models\PendingSend::STATUS_RETRY_WAITING] ?? 0) }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-sm font-semibold text-ink">Recent delivery activity</h2>
            <p class="mt-1 text-xs text-ink-tertiary">This history is limited to your account. Sensitive transport error text and message contents are not shown here.</p>
        </div>
        <div class="w-full sm:w-56">
            <label for="delivery-status-filter" class="sr-only">Filter by delivery status</label>
            <select
                id="delivery-status-filter"
                wire:model.live="statusFilter"
                class="w-full rounded-lg border border-border bg-surface-raised px-3 py-2 text-xs text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($sends->isEmpty())
        <div class="rounded-xl border border-dashed border-border bg-surface-raised px-5 py-12 text-center">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-sunken text-ink-tertiary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5h16v13H4zM4 7l8 6 8-6"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-ink">No delivery records found</h3>
            <p class="mt-1 text-xs text-ink-tertiary">Messages sent or queued from OpenMail will appear here.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($sends as $send)
                @php
                    $statusMeta = match($send->status) {
                        \App\Models\PendingSend::STATUS_SENT => [
                            'label' => 'Sent',
                            'class' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
                            'description' => 'SMTP delivery was accepted.',
                        ],
                        \App\Models\PendingSend::STATUS_PENDING => [
                            'label' => $send->is_scheduled ? 'Scheduled' : 'Queued',
                            'class' => 'border-border bg-surface-sunken text-ink-secondary',
                            'description' => $send->is_scheduled ? 'Waiting for the selected delivery time.' : 'Waiting for the delivery worker.',
                        ],
                        \App\Models\PendingSend::STATUS_CLAIMED,
                        \App\Models\PendingSend::STATUS_PROCESSING,
                        \App\Models\PendingSend::STATUS_CANCELLING => [
                            'label' => str($send->status)->replace('_', ' ')->title()->toString(),
                            'class' => 'border-border bg-surface-sunken text-ink-secondary',
                            'description' => 'The delivery worker is processing this record.',
                        ],
                        \App\Models\PendingSend::STATUS_RETRY_WAITING => [
                            'label' => 'Retry waiting',
                            'class' => 'border-warning/25 bg-warning/10 text-warning',
                            'description' => 'A retry is queued after a recoverable failure.',
                        ],
                        \App\Models\PendingSend::STATUS_CANCELLED => [
                            'label' => 'Cancelled',
                            'class' => 'border-border bg-surface-sunken text-ink-tertiary',
                            'description' => 'The message was cancelled before dispatch.',
                        ],
                        \App\Models\PendingSend::STATUS_FAILED => [
                            'label' => 'Failed',
                            'class' => 'border-error/25 bg-error/10 text-error',
                            'description' => 'Delivery failed or was rejected. Review the recipient and mail-server configuration before manually sending another copy.',
                        ],
                        \App\Models\PendingSend::STATUS_UNCERTAIN => [
                            'label' => 'Uncertain delivery',
                            'class' => 'border-warning/30 bg-warning/10 text-warning',
                            'description' => 'The SMTP connection ended without a reliable final outcome. The server may have accepted the message; check Sent or the recipient before sending another copy.',
                        ],
                        default => [
                            'label' => str($send->status)->replace('_', ' ')->title()->toString(),
                            'class' => 'border-border bg-surface-sunken text-ink-secondary',
                            'description' => 'Check the message status before taking further action.',
                        ],
                    };
                    $messageData = $send->message_json ?? [];
                @endphp
                <article wire:key="delivery-record-{{ $send->id }}" class="flex flex-col gap-3 p-4 sm:p-5">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="break-words text-sm font-semibold text-ink">{{ $messageData['subject'] ?? '(No subject)' }}</h3>
                            <p class="mt-1 break-words text-xs text-ink-secondary">To: {{ $messageData['to'] ?? '(Recipient unavailable)' }}</p>
                            @if(! empty($messageData['cc']))
                                <p class="mt-0.5 break-words text-xs text-ink-tertiary">Cc: {{ $messageData['cc'] }}</p>
                            @endif
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full border px-2.5 py-1 text-[10px] font-semibold {{ $statusMeta['class'] }}">
                            {{ $statusMeta['label'] }}
                        </span>
                    </div>

                    <p class="text-xs leading-5 text-ink-secondary">{{ $statusMeta['description'] }}</p>

                    <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-ink-tertiary">
                        <span>Created {{ $send->created_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'time unavailable' }}</span>
                        @if($send->is_scheduled && in_array($send->status, [\App\Models\PendingSend::STATUS_PENDING, \App\Models\PendingSend::STATUS_RETRY_WAITING], true))
                            <span>Scheduled for {{ $send->send_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'time unavailable' }}</span>
                        @elseif($send->status === \App\Models\PendingSend::STATUS_RETRY_WAITING)
                            <span>Next attempt {{ $send->send_at?->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? 'time unavailable' }}</span>
                        @elseif($send->delivered_at)
                            <span>Delivered at {{ $send->delivered_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
                        @endif
                        @if($send->last_error_category)
                            <span>Last error category: {{ str($send->last_error_category)->replace('_', ' ')->toString() }}</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="px-1">{{ $sends->links() }}</div>
    @endif
</div>
