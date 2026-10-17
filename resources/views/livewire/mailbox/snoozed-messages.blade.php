<div class="space-y-3">
    @if($messages->isEmpty())
        <div class="rounded-xl border border-dashed border-border bg-surface-raised px-5 py-12 text-center">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-sunken text-ink-tertiary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-ink">Nothing snoozed</h2>
            <p class="mt-1 text-xs text-ink-tertiary">Snoozed messages disappear from their OpenMail folder until the selected return time.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($messages as $message)
                <article wire:key="snoozed-message-{{ $message->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="truncate text-sm font-semibold text-ink">{{ $message->subject ?: '(No subject)' }}</h3>
                        <p class="mt-0.5 truncate text-xs text-ink-secondary">{{ $message->sender ?: 'Unknown sender' }}</p>
                        <p class="mt-1 text-xs text-ink-tertiary">
                            Returns {{ $message->wake_at->timezone(config('app.timezone'))->format('M j, Y \\a\\t g:i A') }}
                            <span aria-hidden="true"> · </span>
                            {{ $message->folder_path }}
                        </p>
                        <a
                            href="{{ route('message.show', ['folderPath' => $message->folder_path, 'uid' => $message->uid]) }}"
                            wire:navigate
                            class="mt-2 inline-flex text-xs font-medium text-primary hover:underline"
                        >Open message</a>
                    </div>
                    <button
                        type="button"
                        wire:click="returnNow({{ $message->id }})"
                        wire:loading.attr="disabled"
                        wire:target="returnNow({{ $message->id }})"
                        class="inline-flex shrink-0 items-center justify-center rounded-lg border border-border px-3 py-2 text-xs font-medium text-ink-secondary hover:bg-hover hover:text-ink disabled:opacity-50"
                    >
                        Return now
                    </button>
                </article>
            @endforeach
        </div>

        <div class="px-1">{{ $messages->links() }}</div>
    @endif
</div>
