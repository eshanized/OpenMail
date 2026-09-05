<div class="message-viewer">
    {{-- Back button --}}
    <div class="mb-6">
        <a
            href="{{ route('mailbox', ['folderPath' => $folderPath]) }}"
            wire:navigate
            class="inline-flex items-center text-gray-600 hover:text-gray-900 font-medium"
        >
            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to folder
        </a>
    </div>

    @if(empty($subject) && empty($fromDisplay))
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-lg text-gray-500">Message not found</p>
        </div>
    @else
        {{-- Message header section --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            {{-- Subject --}}
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $subject }}</h1>

            {{-- From/To/Date --}}
            <div class="space-y-3 text-sm">
                <div class="flex items-start">
                    <span class="text-gray-500 w-20 flex-shrink-0 font-medium">From:</span>
                    <span class="text-gray-900">{{ $fromDisplay }}</span>
                </div>

                <div class="flex items-start">
                    <span class="text-gray-500 w-20 flex-shrink-0 font-medium">To:</span>
                    <span class="text-gray-900">{{ $toDisplay }}</span>
                </div>

                <div class="flex items-start">
                    <span class="text-gray-500 w-20 flex-shrink-0 font-medium">Date:</span>
                    <span class="text-gray-900">{{ $formattedDate }}</span>
                </div>

                {{-- Expandable details --}}
                <div class="border-t border-gray-100 pt-3">
                    <button
                        type="button"
                        @click="$dispatch('toggle-details')"
                        x-data="{ open: false }"
                        @toggle-details.window="open = !open"
                        class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center"
                    >
                        <span x-show="!open">Show details</span>
                        <span x-show="open">Hide details</span>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" :class="{ 'rotate-180': open }">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="open" x-transition class="mt-3 space-y-2 text-sm">
                        <div class="flex items-start">
                            <span class="text-gray-500 w-20 flex-shrink-0 font-medium">Cc:</span>
                            <span class="text-gray-900">{{ $ccDisplay }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-500 w-20 flex-shrink-0 font-medium">Bcc:</span>
                            <span class="text-gray-900">{{ $bccDisplay }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-500 w-20 flex-shrink-0 font-medium">Message-ID:</span>
                            <span class="text-gray-900 font-mono text-xs break-all">{{ $messageId }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-500 w-20 flex-shrink-0 font-medium">In-Reply-To:</span>
                            <span class="text-gray-900 font-mono text-xs break-all">{{ $inReplyTo }}</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-gray-500 w-20 flex-shrink-0 font-medium">References:</span>
                            <span class="text-gray-900 font-mono text-xs break-all">{{ $references }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="mt-4 pt-4 border-t border-gray-100 flex flex-wrap items-center gap-2">
                {{-- Star toggle --}}
                <button
                    wire:click="toggleStar"
                    wire:loading.attr="disabled"
                    class="p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-yellow-500 transition-colors disabled:opacity-50"
                    title="{{ $isFlagged ? 'Unstar' : 'Star' }}"
                >
                    @if($isFlagged)
                        <svg class="w-5 h-5 fill-current text-yellow-500" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                    @endif
                </button>

                {{-- Mark read/unread --}}
                <button
                    wire:click="toggleRead"
                    wire:loading.attr="disabled"
                    class="p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors disabled:opacity-50"
                    title="{{ $isSeen ? 'Mark unread' : 'Mark read' }}"
                >
                    @if($isSeen)
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    @else
                        <svg class="w-5 h-5 fill-current text-blue-600" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
                        </svg>
                    @endif
                </button>

                {{-- Move to folder dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        @keydown.escape="open = false"
                        @click.outside="open = false"
                        class="p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors flex items-center"
                        title="Move to folder"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                        </svg>
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
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
                        class="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                    >
                        @foreach($folders as $folder)
                            @if($folder['path'] !== $folderPath)
                                <button
                                    wire:click="moveToFolder('{{ $folder['path'] }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 disabled:opacity-50 flex items-center"
                                >
                                    @if($folder['role'])
                                        <span class="w-5 h-5 mr-2 text-gray-400">
                                            @if($folder['role'] === 'inbox')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                            @elseif($folder['role'] === 'sent')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                            @elseif($folder['role'] === 'drafts')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828l6.586 6.586a2 2 0 002.828-2.828L10.828 2.172a2 2 0 00-2.828 0L2.172 9.828a2 2 0 000 2.828l6.586 6.586a2 2 0 102.828-2.828z"></path></svg>
                                            @elseif($folder['role'] === 'trash')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            @elseif($folder['role'] === 'spam')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            @elseif($folder['role'] === 'archive')
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                            @else
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                            @endif
                                        </span>
                                    @endif
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
                    class="p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-red-600 transition-colors disabled:opacity-50"
                    title="Move to Trash"
                    onclick="return confirm('Move this message to Trash?')"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>

                {{-- Print --}}
                <button
                    onclick="window.print()"
                    class="p-2 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition-colors"
                    title="Print"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- Message body --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            @if($sanitizedHtml)
                <x-email-renderer :html="$sanitizedHtml" :showImages="$showImages" />
            @elseif($textBody)
                <pre class="whitespace-pre-wrap font-sans text-sm text-gray-800 bg-gray-50 p-6 overflow-x-auto">{{ $textBody }}</pre>
            @else
                <div class="p-12 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-lg">No content available</p>
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