<div
    x-data="commandPalette({
        mailboxUrl: @js(route('mailbox')),
        inboxUrl: @js(route('mailbox.folder', ['folderPath' => 'INBOX'])),
        settingsUrl: @js(route('settings')),
        scheduledUrl: @js(route('scheduled-sends')),
        snoozedUrl: @js(route('snoozed-messages')),
        savedSearchesUrl: @js(route('saved-searches')),
        templatesUrl: @js(route('email-templates')),
        deliveryActivityUrl: @js(route('delivery-activity'))
    })"
    @keydown.window="handleKeydown($event)"
>
    <button
        type="button"
        @click="openPalette()"
        class="fixed bottom-4 left-4 z-30 hidden items-center gap-2 rounded-xl border border-border bg-surface-raised/95 px-3 py-2 text-xs font-medium text-ink-secondary shadow-lg backdrop-blur transition hover:text-ink sm:inline-flex"
        aria-label="Open command palette"
        title="Command palette (Ctrl or Cmd + K)"
    >
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
            <circle cx="10.8" cy="10.8" r="6.8"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.2 4.2"/>
        </svg>
        <span>Quick actions</span>
        <kbd class="rounded border border-border bg-surface-sunken px-1.5 py-0.5 text-[10px]">⌘ K</kbd>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-[100] flex items-start justify-center bg-black/40 px-3 pt-[12vh] backdrop-blur-[2px] sm:px-6"
        @click.self="closePalette()"
        role="presentation"
    >
        <section
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-[0.98]"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="w-full max-w-xl overflow-hidden rounded-2xl border border-border bg-surface-raised shadow-2xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="command-palette-title"
            aria-describedby="command-palette-help"
        >
            <div class="flex items-center gap-3 border-b border-border px-4">
                <svg class="h-5 w-5 shrink-0 text-ink-tertiary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.2 4.2"/>
                </svg>
                <label id="command-palette-title" for="openmail-command-query" class="sr-only">Quick actions</label>
                <input
                    id="openmail-command-query"
                    x-ref="queryInput"
                    x-model="query"
                    @input="activeIndex = 0"
                    type="search"
                    autocomplete="off"
                    spellcheck="false"
                    class="min-w-0 flex-1 border-0 bg-transparent py-4 text-sm text-ink outline-none placeholder:text-ink-tertiary focus:ring-0"
                    placeholder="Search commands, pages, and actions…"
                    aria-label="Search commands"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-controls="openmail-command-results"
                    :aria-expanded="open ? 'true' : 'false'"
                >
                <kbd class="hidden rounded-md border border-border bg-surface-sunken px-2 py-1 text-[10px] text-ink-tertiary sm:inline">ESC</kbd>
            </div>

            <div id="command-palette-help" class="flex items-center justify-between px-4 pt-3 text-[10px] text-ink-tertiary">
                <span>Navigate with ↑ ↓ and press Enter</span>
                <span x-text="filteredActions.length + ' action' + (filteredActions.length === 1 ? '' : 's')"></span>
            </div>

            <div id="openmail-command-results" role="listbox" aria-label="Available commands" class="max-h-[min(50vh,360px)] overflow-y-auto p-2">
                <template x-for="(action, index) in filteredActions" :key="action.id">
                    <button
                        type="button"
                        role="option"
                        :aria-selected="index === activeIndex ? 'true' : 'false'"
                        @mouseenter="activeIndex = index"
                        @click="execute(action)"
                        :class="index === activeIndex ? 'bg-primary/10 text-primary' : 'text-ink-secondary hover:bg-hover hover:text-ink'"
                        class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left transition-colors"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-surface text-ink-tertiary" aria-hidden="true">
                            <svg x-show="action.icon === 'compose'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                            </svg>
                            <svg x-show="action.icon === 'inbox'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8h18v11H3zM3 14h5l2 3h4l2-3h5M7 4h10"/>
                            </svg>
                            <svg x-show="action.icon === 'search'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.2 4.2"/>
                            </svg>
                            <svg x-show="action.icon === 'clock'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/>
                            </svg>
                            <svg x-show="action.icon === 'template'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-9a1.5 1.5 0 00-1.5-1.5h-12a1.5 1.5 0 00-1.5 1.5v15l4.5-3h9a1.5 1.5 0 001.5-1.5v-1.5"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 8.25h7.5m-7.5 3h7.5"/>
                            </svg>
                            <svg x-show="action.icon === 'settings'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.7 1.7 0 00.34 1.87l.06.06-1.7 2.94-.09-.02a1.7 1.7 0 00-1.77.74l-.05.08h-3.4l-.05-.08a1.7 1.7 0 00-1.77-.74l-.09.02-1.7-2.94.06-.06A1.7 1.7 0 008.4 15l-.02-.09v-3.4l.08-.05a1.7 1.7 0 00.74-1.77l-.02-.09 2.94-1.7.06.06a1.7 1.7 0 001.87.34l.09-.02h3.4l.05.08a1.7 1.7 0 001.77.74l.09-.02 1.7 2.94-.06.06A1.7 1.7 0 0019.4 15z"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-medium" x-text="action.label"></span>
                            <span class="mt-0.5 block truncate text-xs opacity-70" x-text="action.description"></span>
                        </span>
                        <kbd x-show="action.shortcut" x-text="action.shortcut" class="rounded border border-border px-1.5 py-0.5 text-[10px]"></kbd>
                    </button>
                </template>

                <div x-show="filteredActions.length === 0" class="px-4 py-10 text-center">
                    <p class="text-sm font-medium text-ink">No matching actions</p>
                    <p class="mt-1 text-xs text-ink-tertiary">Try a shorter or different search.</p>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-border bg-surface-sunken/60 px-4 py-2.5 text-[10px] text-ink-tertiary">
                <span>OpenMail</span>
                <span>Quick navigation and actions</span>
            </div>
        </section>
    </div>
</div>
