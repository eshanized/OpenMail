<div class="space-y-3">
    @if($reminders->isEmpty())
        <div class="rounded-xl border border-dashed border-border bg-surface-raised px-5 py-12 text-center">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-sunken text-ink-tertiary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-ink">No follow-ups yet</h2>
            <p class="mt-1 text-xs text-ink-tertiary">Set a reminder from a message when you need to follow up later.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($reminders as $reminder)
                @php($isDue = $reminder->remind_at->lessThanOrEqualTo(now()))
                <article wire:key="follow-up-reminder-{{ $reminder->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $isDue ? 'bg-warning/10 text-warning' : 'bg-primary/10 text-primary' }}">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate text-sm font-semibold text-ink">{{ $reminder->subject ?: '(No subject)' }}</h3>
                            @if($isDue)
                                <span class="rounded-full border border-warning/25 bg-warning/10 px-2 py-0.5 text-[10px] font-semibold text-warning">Due</span>
                            @else
                                <span class="rounded-full border border-border bg-surface-sunken px-2 py-0.5 text-[10px] font-semibold text-ink-tertiary">Upcoming</span>
                            @endif
                        </div>
                        <p class="mt-0.5 truncate text-xs text-ink-secondary">{{ $reminder->sender ?: 'Unknown sender' }}</p>
                        <p class="mt-1 text-xs text-ink-tertiary">
                            Remind at {{ $reminder->remind_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}
                            <span aria-hidden="true"> · </span>
                            {{ $reminder->folder_path }}
                        </p>
                        <a
                            href="{{ route('message.show', ['folderPath' => $reminder->folder_path, 'uid' => $reminder->uid]) }}"
                            wire:navigate
                            class="mt-2 inline-flex text-xs font-medium text-primary hover:underline"
                        >Open message</a>
                    </div>
                    <div class="flex shrink-0 flex-wrap gap-2">
                        <button
                            type="button"
                            wire:click="completeReminder({{ $reminder->id }})"
                            wire:loading.attr="disabled"
                            wire:target="completeReminder({{ $reminder->id }})"
                            class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-50"
                        >Complete</button>
                        <button
                            type="button"
                            wire:click="dismissReminder({{ $reminder->id }})"
                            wire:confirm="Dismiss this follow-up reminder?"
                            wire:loading.attr="disabled"
                            wire:target="dismissReminder({{ $reminder->id }})"
                            class="rounded-lg border border-border px-3 py-2 text-xs font-medium text-ink-secondary hover:bg-hover disabled:opacity-50"
                        >Dismiss</button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="px-1">{{ $reminders->links() }}</div>
    @endif
</div>
