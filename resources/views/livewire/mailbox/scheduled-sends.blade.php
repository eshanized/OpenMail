<div class="space-y-3">
    @if($sends->isEmpty())
        <div class="rounded-xl border border-dashed border-border bg-surface-raised px-5 py-12 text-center">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-sunken text-ink-tertiary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-ink">Nothing scheduled</h2>
            <p class="mt-1 text-xs text-ink-tertiary">Messages you schedule for later will appear here until they are sent or cancelled.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($sends as $send)
                @php($messageData = $send->message_json ?? [])
                <article wire:key="scheduled-send-{{ $send->id }}" class="flex flex-col sm:flex-row sm:items-center gap-3 p-4">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-ink">{{ $messageData['subject'] ?? '(No subject)' }}</h3>
                        <p class="mt-0.5 truncate text-xs text-ink-secondary">To: {{ $messageData['to'] ?? '(No recipient)' }}</p>
                        <p class="mt-1 text-xs text-ink-tertiary">
                            {{ $send->send_at?->timezone(config('app.timezone'))->format('M j, Y \\a\\t g:i A') ?? 'Delivery time unavailable' }}
                            <span aria-hidden="true"> · </span>
                            @if($send->status === \App\Models\PendingSend::STATUS_RETRY_WAITING)
                                Retry scheduled
                            @elseif($send->send_at && $send->send_at->isPast())
                                Due — awaiting delivery
                            @else
                                Scheduled
                            @endif
                        </p>
                        @if($send->last_error_category)
                            <p class="mt-1 text-xs text-warning">Previous delivery attempt: {{ str_replace('_', ' ', $send->last_error_category) }}. The message remains queued for retry.</p>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="cancelScheduled({{ $send->id }})"
                        wire:confirm="Cancel this scheduled message and save it to Drafts?"
                        wire:loading.attr="disabled"
                        wire:target="cancelScheduled({{ $send->id }})"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-border px-3 py-2 text-xs font-medium text-ink-secondary hover:border-error/40 hover:bg-error/5 hover:text-error disabled:opacity-50"
                    >
                        Cancel schedule
                    </button>
                </article>
            @endforeach
        </div>

        <div class="px-1">
            {{ $sends->links() }}
        </div>
    @endif
</div>
