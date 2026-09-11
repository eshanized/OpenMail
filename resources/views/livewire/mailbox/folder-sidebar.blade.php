<div x-data="{ expandedFolders: {}, activeTab: @js($activeTab ?? 'folders') }"
     @set-active-tab.window="activeTab = $event.detail.tab"
     x-init="$wire.on('active-tab-changed', (tab) => { activeTab = tab; });"
     class="h-full flex flex-col min-h-0 bg-surface-raised select-none">

    {{-- Desktop Tab Switcher — minimal, not segmented pill --}}
    <div class="hidden md:block px-3 pt-3 pb-2 border-b border-border flex-shrink-0">
        <div class="flex gap-0.5 text-xs">
            <button
                type="button"
                @click="$wire.setActiveTab('folders')"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded transition-colors duration-100 cursor-pointer
                    {{ $activeTab === 'folders'
                        ? 'bg-primary-subtle text-primary font-medium'
                        : 'text-ink-tertiary hover:text-ink hover:bg-hover' }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
                Folders
            </button>
            <button
                type="button"
                @click="$wire.setActiveTab('contacts')"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded transition-colors duration-100 cursor-pointer
                    {{ $activeTab === 'contacts'
                        ? 'bg-primary-subtle text-primary font-medium'
                        : 'text-ink-tertiary hover:text-ink hover:bg-hover' }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Contacts
            </button>
            <button
                type="button"
                @click="$wire.setActiveTab('labels')"
                class="flex items-center gap-1.5 px-2.5 py-1.5 rounded transition-colors duration-100 cursor-pointer
                    {{ $activeTab === 'labels'
                        ? 'bg-primary-subtle text-primary font-medium'
                        : 'text-ink-tertiary hover:text-ink hover:bg-hover' }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                </svg>
                Labels
            </button>
        </div>
    </div>

    {{-- Tab Panels --}}
    <div class="flex-1 overflow-y-auto scrollbar-thin min-h-0">
        {{-- Folders Panel --}}
        <div x-show="activeTab === 'folders'" x-cloak class="flex flex-col min-h-full">
            {{-- Compose Action — prominent but clean --}}
            <div class="px-3 py-3 flex-shrink-0">
                <button
                    type="button"
                    @click="$dispatch('openComposer', { mode: 'compose' })"
                    class="w-full text-sm font-medium py-1.5 px-3 rounded border border-primary/30 bg-primary/5 text-primary hover:bg-primary/10 hover:border-primary/40 flex items-center gap-2 transition-colors duration-100 cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                    <span>New Message</span>
                    <kbd class="ml-auto text-[10px] font-medium text-ink-tertiary bg-surface-sunken border border-border px-1.5 py-0.5 rounded">C</kbd>
                </button>
            </div>

            {{-- Skeleton loading during refreshFolders --}}
            <div wire:loading wire:target="refreshFolders" class="space-y-1 px-3 py-1" aria-hidden="true">
                @foreach([1, 2, 3, 4, 5] as $i)
                    <div class="flex items-center px-2 py-1.5 gap-3">
                        <div class="skeleton h-3.5 w-3.5 rounded"></div>
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="skeleton h-3 rounded w-3/4"></div>
                        </div>
                        <div class="skeleton h-3 w-5 rounded"></div>
                    </div>
                @endforeach
            </div>

            {{-- Folders Content --}}
            <div wire:loading.remove wire:target="refreshFolders" class="flex-1 flex flex-col px-2 py-1">
                @php
                    $primaryRoles = ['inbox', 'drafts', 'sent', 'archive'];
                    $cleanupRoles = ['spam', 'trash'];

                    $coreFolders = array_filter($folders, fn($f) => in_array($f['role'] ?? '', $primaryRoles));
                    $customFolders = array_filter($folders, fn($f) => empty($f['role']));
                    $cleanupFolders = array_filter($folders, fn($f) => in_array($f['role'] ?? '', $cleanupRoles));

                    // Fallback: if no roles matched (raw IMAP), render all folders directly
                    if (empty($coreFolders) && empty($cleanupFolders)) {
                        $coreFolders = $folders;
                    }
                @endphp

                {{-- Core Mailboxes --}}
                <div class="space-y-0.5">
                    @foreach($coreFolders as $folder)
                        <div
                            class="relative flex items-center px-2 py-1.5 rounded cursor-pointer transition-colors duration-100 group
                                {{ $folder['path'] === $currentFolder
                                    ? 'bg-primary-subtle text-primary'
                                    : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                            wire:click="selectFolder('{{ $folder['path'] }}')"
                        >
                            {{-- Active indicator — subtle left strip --}}
                            @if($folder['path'] === $currentFolder)
                                <span class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-primary rounded-r" aria-hidden="true"></span>
                            @endif

                            {{-- Folder Icon — simple, no colored background --}}
                            <span class="w-4 h-4 mr-2.5 flex-shrink-0 text-ink-tertiary {{ $folder['path'] === $currentFolder ? 'text-primary' : '' }}">
                                @switch($folder['role'] ?? '')
                                    @case('inbox')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-16.5 0a2.25 2.25 0 00-1.957 1.148l-.07.155a2.25 2.25 0 01-1.957 1.148H2.25M21 13.5V9.75c0-1.108-.892-2-2-2h-3.75m-3 0h-3.75M2.25 9.75V16.5c0 1.108.892 2 2 2h3.75m-3 0h13.5c1.108 0 2-.892 2-2V9.75"/>
                                        </svg>
                                        @break
                                    @case('drafts')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                        @break
                                    @case('sent')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                        </svg>
                                        @break
                                    @case('archive')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                        </svg>
                                @endswitch
                            </span>

                            <span class="flex-1 truncate text-sm {{ $folder['path'] === $currentFolder ? 'font-medium' : '' }}">
                                {{ $folder['name'] }}
                            </span>

                            {{-- Count — unread as text, not a pill --}}
                            <div class="flex items-center ml-2">
                                @if(($folder['unread_count'] ?? 0) > 0)
                                    <span class="text-xs font-semibold tabular-nums {{ $folder['path'] === $currentFolder ? 'text-primary' : 'text-ink-secondary' }}">
                                        {{ ($folder['unread_count'] ?? 0) > 99 ? '99+' : $folder['unread_count'] }}
                                    </span>
                                @elseif(($folder['total_count'] ?? $folder['total'] ?? 0) > 0)
                                    <span class="text-xs text-ink-tertiary tabular-nums">{{ $folder['total_count'] ?? $folder['total'] }}</span>
                                @endif
                            </div>

                            {{-- Expand/collapse for folders with children --}}
                            @if(!empty($folder['has_children']))
                                <button
                                    type="button"
                                    class="ml-1 text-ink-tertiary hover:text-ink p-0.5 rounded hover:bg-hover transition-colors cursor-pointer"
                                    @click.stop="expandedFolders['{{ $folder['path'] }}'] = !expandedFolders['{{ $folder['path'] }}']]"
                                    aria-label="Toggle folder tree"
                                >
                                    <svg class="w-3 h-3 transition-transform duration-100"
                                         :class="{ 'rotate-90': expandedFolders['{{ $folder['path'] }}'] }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Custom Folders Section --}}
                @if(!empty($customFolders))
                    <div class="mt-4">
                        <div class="px-2 pb-1 text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider">
                            Folders
                        </div>
                        <div class="space-y-0.5">
                            @foreach($customFolders as $folder)
                                <div
                                    class="relative flex items-center px-2 py-1.5 rounded cursor-pointer transition-colors duration-100
                                        {{ $folder['path'] === $currentFolder
                                            ? 'bg-primary-subtle text-primary'
                                            : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                                    wire:click="selectFolder('{{ $folder['path'] }}')"
                                >
                                    @if($folder['path'] === $currentFolder)
                                        <span class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-primary rounded-r" aria-hidden="true"></span>
                                    @endif

                                    <svg class="w-4 h-4 mr-2.5 flex-shrink-0 text-ink-tertiary {{ $folder['path'] === $currentFolder ? 'text-primary' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                    </svg>
                                    <span class="flex-1 truncate text-sm {{ $folder['path'] === $currentFolder ? 'font-medium' : '' }}">{{ $folder['name'] }}</span>
                                    <div class="ml-2">
                                        @if(($folder['unread_count'] ?? 0) > 0)
                                            <span class="text-xs font-semibold tabular-nums {{ $folder['path'] === $currentFolder ? 'text-primary' : 'text-ink-secondary' }}">{{ $folder['unread_count'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Cleanup / Secondary Folders (Spam & Trash) --}}
                @if(!empty($cleanupFolders))
                    <div class="mt-4 pt-3 border-t border-border-subtle space-y-0.5">
                        @foreach($cleanupFolders as $folder)
                            <div
                                class="relative flex items-center px-2 py-1.5 rounded cursor-pointer transition-colors duration-100
                                    {{ $folder['path'] === $currentFolder
                                        ? 'bg-primary-subtle text-primary'
                                        : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                                wire:click="selectFolder('{{ $folder['path'] }}')"
                            >
                                @if($folder['path'] === $currentFolder)
                                    <span class="absolute left-0 top-1.5 bottom-1.5 w-0.5 bg-primary rounded-r" aria-hidden="true"></span>
                                @endif

                                @if(($folder['role'] ?? '') === 'trash')
                                    <svg class="w-4 h-4 mr-2.5 flex-shrink-0 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                @else
                                    <svg class="w-4 h-4 mr-2.5 flex-shrink-0 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                    </svg>
                                @endif

                                <span class="flex-1 truncate text-sm {{ $folder['path'] === $currentFolder ? 'font-medium' : '' }}">
                                    {{ $folder['name'] }}
                                </span>

                                <div class="ml-2">
                                    @if(($folder['unread_count'] ?? 0) > 0)
                                        <span class="text-xs font-semibold tabular-nums {{ $folder['path'] === $currentFolder ? 'text-primary' : 'text-ink-secondary' }}">{{ $folder['unread_count'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Sync Footer — minimal --}}
            <div class="mt-auto px-3 py-2.5 border-t border-border flex-shrink-0">
                <button
                    wire:click="refreshFolders"
                    wire:loading.attr="disabled"
                    wire:target="refreshFolders"
                    class="w-full text-xs text-ink-tertiary hover:text-ink flex items-center gap-2 py-1 px-1 rounded hover:bg-hover transition-colors cursor-pointer group disabled:opacity-40"
                >
                    <svg wire:loading.remove wire:target="refreshFolders" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                    </svg>
                    <svg wire:loading wire:target="refreshFolders" class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                    </svg>
                    <span wire:loading.remove wire:target="refreshFolders">Sync Mailbox</span>
                    <span wire:loading wire:target="refreshFolders">Syncing…</span>
                </button>
            </div>
        </div>

        {{-- Contacts Panel --}}
        <div x-show="activeTab === 'contacts'" x-cloak class="h-full">
            <livewire:mailbox.contact-sidebar :activeTab="$activeTab === 'contacts'" wire:key="contact-sidebar" />
        </div>

        {{-- Labels Panel --}}
        <div x-show="activeTab === 'labels'" x-cloak class="h-full">
            <livewire:mailbox.label-sidebar :activeTab="$activeTab === 'labels'" wire:key="label-sidebar" />
        </div>
    </div>

    {{-- Mobile Bottom Navigation --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-raised border-t border-border"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex">
            <button
                type="button"
                @click="$wire.setActiveTab('folders')"
                class="flex-1 flex flex-col items-center justify-center py-2 transition-colors cursor-pointer
                    {{ $activeTab === 'folders' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
                <span class="text-[10px] mt-0.5">Folders</span>
            </button>
            <button
                type="button"
                @click="$wire.setActiveTab('contacts')"
                class="flex-1 flex flex-col items-center justify-center py-2 transition-colors cursor-pointer
                    {{ $activeTab === 'contacts' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <span class="text-[10px] mt-0.5">Contacts</span>
            </button>
            <button
                type="button"
                @click="$wire.setActiveTab('labels')"
                class="flex-1 flex flex-col items-center justify-center py-2 transition-colors cursor-pointer
                    {{ $activeTab === 'labels' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                </svg>
                <span class="text-[10px] mt-0.5">Labels</span>
            </button>
        </div>
    </div>
</div>
