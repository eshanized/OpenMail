<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-base font-semibold text-ink">Your templates</h2>
            <p class="mt-1 text-xs text-ink-tertiary">Save reusable message text for common replies, introductions, and follow-ups.</p>
        </div>
        <button
            type="button"
            wire:click="createTemplate"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary-hover"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/>
            </svg>
            New template
        </button>
    </div>

    @if($showForm)
        <form wire:submit="saveTemplate" class="space-y-3 rounded-xl border border-border bg-surface-raised p-4">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-semibold text-ink">{{ $editingTemplateId ? 'Edit template' : 'Create template' }}</h3>
                <button type="button" wire:click="cancelEdit" class="rounded p-1 text-ink-tertiary hover:bg-hover hover:text-ink" aria-label="Close template form">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div>
                <label for="template-name" class="mb-1 block text-xs font-medium text-ink-secondary">Template name</label>
                <input id="template-name" type="text" maxlength="100" required wire:model="name" class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="e.g. Meeting follow-up">
                @error('name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="template-subject" class="mb-1 block text-xs font-medium text-ink-secondary">Subject (optional)</label>
                <input id="template-subject" type="text" maxlength="255" wire:model="subject" class="w-full rounded-lg border border-border bg-surface px-3 py-2 text-sm text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Subject line">
                @error('subject') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="template-body" class="mb-1 block text-xs font-medium text-ink-secondary">Message text</label>
                <textarea id="template-body" rows="7" required maxlength="20000" wire:model="body" class="w-full resize-y rounded-lg border border-border bg-surface px-3 py-2 text-sm leading-6 text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Write the reusable message text…"></textarea>
                @error('body') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
                <p class="mt-1 text-[11px] text-ink-tertiary">Templates are plain text. Line breaks are preserved when inserted into the composer.</p>
            </div>

            <div class="flex flex-wrap justify-end gap-2">
                <button type="button" wire:click="cancelEdit" class="rounded-lg border border-border px-3 py-2 text-xs font-medium text-ink-secondary hover:bg-hover">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveTemplate" class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary-hover disabled:opacity-50">
                    <span wire:loading.remove wire:target="saveTemplate">Save template</span>
                    <span wire:loading wire:target="saveTemplate">Saving…</span>
                </button>
            </div>
        </form>
    @endif

    @if($templates->isEmpty())
        <div class="rounded-xl border border-dashed border-border px-5 py-12 text-center">
            <h3 class="text-sm font-semibold text-ink">No templates yet</h3>
            <p class="mt-1 text-xs text-ink-tertiary">Create a template and it will be available directly from the email composer.</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($templates as $template)
                <article wire:key="email-template-{{ $template->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-9a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v15l4.5-3h9a1.5 1.5 0 001.5-1.5v-1.5"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5m-7.5 3h7.5"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-ink">{{ $template->name }}</h3>
                        @if($template->subject)
                            <p class="mt-1 text-xs font-medium text-ink-secondary">Subject: {{ $template->subject }}</p>
                        @endif
                        <p class="mt-1 line-clamp-3 whitespace-pre-line break-words text-xs leading-5 text-ink-tertiary">{{ $template->body }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" wire:click="editTemplate({{ $template->id }})" class="rounded-lg border border-border px-3 py-2 text-xs font-medium text-ink-secondary hover:bg-hover">Edit</button>
                        <button type="button" wire:click="deleteTemplate({{ $template->id }})" wire:confirm="Delete this template permanently?" class="rounded-lg border border-border px-3 py-2 text-xs font-medium text-error hover:bg-error/5">Delete</button>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
