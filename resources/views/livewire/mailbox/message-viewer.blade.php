<div class="message-viewer max-w-4xl mx-auto">
    {{-- Back navigation --}}
    <div class="mb-4">
        <a
            href="{{ route('mailbox', ['folderPath' => $folderPath]) }}"
            wire:navigate
            class="inline-flex items-center gap-1.5 text-sm text-ink-secondary hover:text-ink font-medium transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
            </svg>
            Back to {{ $folderPath }}
        </a>
    </div>

    @if(empty($subject) && empty($fromDisplay))
        {{-- Not found state --}}
        <div class="bg-surface-raised rounded-xl border border-border p-16 text-center">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-surface-sunken flex items-center justify-center">
                <svg class="w-8 h-8 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                </svg>
            </div>
            <h3 class="text-sm font-medium text-ink mb-1">Message not found</h3>
            <p class="text-xs text-ink-tertiary">This message may have been moved or deleted.</p>
        </div>
    @else
        {{-- Message header --}}
        <div class="bg-surface-raised rounded-xl border border-border overflow-hidden">
            <div class="p-6">
                {{-- Subject --}}
                <h1 class="text-xl font-bold text-ink leading-tight mb-5">{{ $subject }}</h1>

                {{-- Sender info --}}
                <div class="flex items-start gap-3 mb-4">
                    {{-- Avatar --}}
                    <div class="w-10 h-10 rounded-full bg-primary-subtle text-primary flex items-center justify-center text-sm font-semibold flex-shrink-0">
                        {{ strtoupper(substr($fromDisplay ?: ($fromAddress ?: '?'), 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-ink">{{ $fromDisplay ?: $fromAddress }}</span>
                        </div>
                        <div class="flex items-center gap-1 text-xs text-ink-tertiary mt-0.5">
                            <span>to</span>
                            <span class="text-ink-secondary">{{ $toDisplay }}</span>
                        </div>
                    </div>
                    <div class="text-xs text-ink-tertiary flex-shrink-0">
                        {{ $formattedDate }}
                    </div>
                </div>

                {{-- Expandable details --}}
                <div class="border-t border-border-subtle pt-3">
                    <button
                        type="button"
                        x-data="{ open: false }"
                        @click="open = !open"
                        class="text-xs text-primary hover:text-primary-hover font-medium flex items-center gap-1 transition-colors"
                    >
                        <span x-text="open ? 'Hide details' : 'Show details'">Show details</span>
                        <svg class="w-3.5 h-3.5 transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>

                    <div x-show="open" x-transition class="mt-3 space-y-2 text-xs">
                        <div class="grid grid-cols-[80px_1fr] gap-2">
                            <span class="text-ink-tertiary font-medium">From</span>
                            <span class="text-ink-secondary break-all">
                                @if($fromDisplay && $fromAddress && $fromDisplay !== $fromAddress)
                                    {{ $fromDisplay }} &lt;{{ $fromAddress }}&gt;
                                @else
                                    {{ $fromDisplay ?: $fromAddress }}
                                @endif
                            </span>
                        </div>
                        <div class="grid grid-cols-[80px_1fr] gap-2">
                            <span class="text-ink-tertiary font-medium">To</span>
                            <span class="text-ink-secondary break-all">{{ $toDisplay }}</span>
                        </div>
                        @if($ccDisplay)
                            <div class="grid grid-cols-[80px_1fr] gap-2">
                                <span class="text-ink-tertiary font-medium">Cc</span>
                                <span class="text-ink-secondary break-all">{{ $ccDisplay }}</span>
                            </div>
                        @endif
                        @if($bccDisplay)
                            <div class="grid grid-cols-[80px_1fr] gap-2">
                                <span class="text-ink-tertiary font-medium">Bcc</span>
                                <span class="text-ink-secondary break-all">{{ $bccDisplay }}</span>
                            </div>
                        @endif
                        <div class="grid grid-cols-[80px_1fr] gap-2">
                            <span class="text-ink-tertiary font-medium">Date</span>
                            <span class="text-ink-secondary">{{ $formattedDate }}</span>
                        </div>
                        @if($messageId)
                            <div class="grid grid-cols-[80px_1fr] gap-2">
                                <span class="text-ink-tertiary font-medium">Message-ID</span>
                                <span class="text-ink-secondary font-mono break-all">{{ $messageId }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Action bar --}}
            <div class="px-6 py-3 border-t border-border bg-surface-sunken/50 flex flex-wrap items-center gap-1">
                {{-- Reply --}}
                <button
                    wire:click="reply"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-ink-secondary bg-surface-raised border border-border rounded-lg hover:bg-hover hover:text-ink transition-colors disabled:opacity-50"
                    title="Reply (r)"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                    </svg>
                    Reply
                </button>

                {{-- Reply All --}}
                <button
                    wire:click="replyAll"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-ink-secondary bg-surface-raised border border-border rounded-lg hover:bg-hover hover:text-ink transition-colors disabled:opacity-50"
                    title="Reply All (Shift+R)"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                    </svg>
                    Reply All
                </button>

                {{-- Forward --}}
                <button
                    wire:click="forward"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-ink-secondary bg-surface-raised border border-border rounded-lg hover:bg-hover hover:text-ink transition-colors disabled:opacity-50"
                    title="Forward (f)"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l6-6m0 0l-6-6m6 6H9a6 6 0 000 12h3"/>
                    </svg>
                    Forward
                </button>

                <div class="w-px h-5 bg-border mx-1"></div>

                {{-- Star --}}
                <button
                    wire:click="toggleStar"
                    wire:loading.attr="disabled"
                    class="p-1.5 rounded-lg text-ink-tertiary hover:text-warning hover:bg-hover transition-colors disabled:opacity-50"
                    title="{{ $isFlagged ? 'Unstar' : 'Star' }}"
                >
                    @if($isFlagged)
                        <svg class="w-4 h-4 fill-current text-warning" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                        </svg>
                    @endif
                </button>

                {{-- Mark read/unread --}}
                <button
                    wire:click="toggleRead"
                    wire:loading.attr="disabled"
                    class="p-1.5 rounded-lg text-ink-tertiary hover:text-ink hover:bg-hover transition-colors disabled:opacity-50"
                    title="{{ $isSeen ? 'Mark unread' : 'Mark read' }}"
                >
                    @if($isSeen)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                        </svg>
                    @endif
                </button>

                {{-- Move to folder --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        @keydown.escape="open = false"
                        @click.outside="open = false"
                        class="p-1.5 rounded-lg text-ink-tertiary hover:text-ink hover:bg-hover transition-colors flex items-center"
                        title="Move to folder"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                        </svg>
                    </button>

                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute left-0 mt-1 w-48 bg-surface-overlay rounded-lg shadow-lg border border-border py-1 z-50"
                    >
                        @foreach($folders as $folder)
                            @if($folder['path'] !== $folderPath)
                                <button
                                    wire:click="moveToFolder('{{ $folder['path'] }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full px-3 py-2 text-left text-xs text-ink-secondary hover:bg-hover hover:text-ink disabled:opacity-50 flex items-center gap-2 transition-colors"
                                >
                                    <span class="w-4 h-4 text-ink-tertiary">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                        </svg>
                                    </span>
                                    {{ $folder['name'] }}
                                </button>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Delete --}}
                <button
                    wire:click="deleteMessage"
                    wire:loading.attr="disabled"
                    class="p-1.5 rounded-lg text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors disabled:opacity-50"
                    title="Move to Trash"
                    onclick="return confirm('Move this message to Trash?')"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>

                {{-- Print --}}
                <button
                    onclick="window.print()"
                    class="p-1.5 rounded-lg text-ink-tertiary hover:text-ink hover:bg-hover transition-colors"
                    title="Print"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.113 48.113 0 018.5 0"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Message body --}}
        <div class="mt-4 bg-surface-raised rounded-xl border border-border overflow-hidden">
            @if($sanitizedHtml)
                <x-email-renderer :html="$sanitizedHtml" :showImages="$showImages" />
            @elseif($textBody)
                <pre class="whitespace-pre-wrap font-sans text-sm text-ink-secondary p-6 leading-relaxed overflow-x-auto">{{ $textBody }}</pre>
            @else
                <div class="p-12 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-surface-sunken flex items-center justify-center">
                        <svg class="w-6 h-6 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <p class="text-sm text-ink-tertiary">No content available</p>
                </div>
            @endif

            {{-- Attachments --}}
            <livewire:mailbox.attachment-list
                :attachments="$attachments"
                :folderPath="$folderPath"
                :uid="$uid"
            />
        </div>
    @endif
</div>
