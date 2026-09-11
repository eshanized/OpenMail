<div class="space-y-6">
    <div class="divide-y divide-border-subtle">
        {{-- Messages per page --}}
        <div class="py-4 first:pt-0 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="max-w-md">
                <label for="page_size" class="text-xs font-semibold uppercase tracking-wider text-ink block">
                    Messages per page
                </label>
                <p class="text-xs text-ink-tertiary mt-0.5">
                    How many conversation threads or emails to load per page.
                </p>
            </div>
            <div class="w-full sm:w-44 shrink-0">
                <select id="page_size" wire:model="pageSize" class="settings-input text-xs">
                    <option value="10">10 messages</option>
                    <option value="25">25 messages (Default)</option>
                    <option value="50">50 messages</option>
                    <option value="100">100 messages</option>
                </select>
            </div>
        </div>

        {{-- Default folder --}}
        <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="max-w-md">
                <label for="default_folder" class="text-xs font-semibold uppercase tracking-wider text-ink block">
                    Default folder
                </label>
                <p class="text-xs text-ink-tertiary mt-0.5">
                    The folder loaded automatically upon signing in.
                </p>
            </div>
            <div class="w-full sm:w-44 shrink-0">
                <select id="default_folder" wire:model="defaultFolder" class="settings-input text-xs">
                    <option value="INBOX">Inbox</option>
                    <option value="Sent">Sent</option>
                    <option value="Drafts">Drafts</option>
                </select>
            </div>
        </div>

        {{-- Reply behavior --}}
        <div class="py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="max-w-md">
                <label for="reply_behavior" class="text-xs font-semibold uppercase tracking-wider text-ink block">
                    Default reply action
                </label>
                <p class="text-xs text-ink-tertiary mt-0.5">
                    Choose whether the primary reply button targets the sender or all recipients.
                </p>
            </div>
            <div class="w-full sm:w-44 shrink-0">
                <select id="reply_behavior" wire:model="replyBehavior" class="settings-input text-xs">
                    <option value="reply">Reply to sender</option>
                    <option value="reply_all">Reply to all</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="pt-3 border-t border-border flex items-center justify-end">
        <button
            wire:click="save"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
        >
            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Save Preferences</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</div>