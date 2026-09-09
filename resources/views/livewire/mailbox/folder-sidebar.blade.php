<div x-data="{ expandedFolders: {}, activeTab: @js($activeTab) }"
     @set-active-tab.window="activeTab = $event.detail.tab"
     x-init="$wire.on('active-tab-changed', (tab) => { activeTab = tab; });"
     class="h-full flex flex-col">

    {{-- Desktop Tab Bar --}}
    <div class="hidden md:flex border-b border-border">
        <button
            @click="$wire.setActiveTab('folders')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-xs font-medium transition-colors border-b-2
                {{ $activeTab === 'folders'
                    ? 'text-primary border-primary bg-primary-subtle/50'
                    : 'text-ink-tertiary border-transparent hover:text-ink-secondary hover:bg-hover' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
            Folders
        </button>
        <button
            @click="$wire.setActiveTab('contacts')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-xs font-medium transition-colors border-b-2
                {{ $activeTab === 'contacts'
                    ? 'text-primary border-primary bg-primary-subtle/50'
                    : 'text-ink-tertiary border-transparent hover:text-ink-secondary hover:bg-hover' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
            </svg>
            Contacts
        </button>
        <button
            @click="$wire.setActiveTab('labels')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-xs font-medium transition-colors border-b-2
                {{ $activeTab === 'labels'
                    ? 'text-primary border-primary bg-primary-subtle/50'
                    : 'text-ink-tertiary border-transparent hover:text-ink-secondary hover:bg-hover' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
            </svg>
            Labels
        </button>
    </div>

    {{-- Tab Panels --}}
    <div class="flex-1 overflow-y-auto scrollbar-thin">
        {{-- Folders Panel --}}
        <div x-show="activeTab === 'folders'" x-cloak>
            {{-- Skeleton loading during refreshFolders --}}
            <div wire:loading wire:target="refreshFolders" class="space-y-1 p-3" aria-hidden="true" style="display: none;">
                @foreach([1, 2, 3] as $i)
                    <div class="flex items-center px-3 py-2.5">
                        <div class="skeleton h-4 w-4 rounded"></div>
                        <div class="flex-1 min-w-0 ml-3 space-y-1.5">
                            <div class="skeleton h-3 rounded w-3/4"></div>
                        </div>
                        <div class="skeleton h-2.5 w-6 rounded"></div>
                    </div>
                @endforeach
            </div>

            {{-- Standard folders --}}
            <div wire:loading.remove wire:target="refreshFolders" class="space-y-0.5 p-2">
                @foreach($folders as $folder)
                    @if($folder['role'])
                        <div
                            class="group flex items-center px-3 py-2 rounded-lg cursor-pointer transition-all duration-100
                                {{ $folder['path'] === $currentFolder
                                    ? 'bg-primary-subtle text-primary font-medium'
                                    : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                            wire:click="selectFolder('{{ $folder['path'] }}')"
                        >
                            {{-- Folder icon --}}
                            <span class="w-5 h-5 mr-3 flex-shrink-0">
                                @switch($folder['role'])
                                    @case('inbox')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 012.012 1.244l.256.512a2.25 2.25 0 002.013 1.244h3.218a2.25 2.25 0 002.013-1.244l.256-.512a2.25 2.25 0 012.013-1.244h3.859m-16.5 0a2.25 2.25 0 00-1.957 1.148l-.07.155a2.25 2.25 0 01-1.957 1.148H2.25M21 13.5V9.75c0-1.108-.892-2-2-2h-3.75m-3 0h-3.75M2.25 9.75V16.5c0 1.108.892 2 2 2h3.75m-3 0h13.5c1.108 0 2-.892 2-2V9.75"/>
                                        </svg>
                                        @break
                                    @case('sent')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                                        </svg>
                                        @break
                                    @case('drafts')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                        </svg>
                                        @break
                                    @case('trash')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                        </svg>
                                        @break
                                    @case('spam')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                        </svg>
                                        @break
                                    @case('archive')
                                        <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
                                        </svg>
                                        @break
                                @endswitch
                            </span>

                            <span class="flex-1 truncate text-sm">
                                {{ $folder['name'] }}
                            </span>

                            {{-- Count badges --}}
                            <div class="flex items-center gap-1.5">
                                @if($folder['unread_count'] > 0)
                                    <span class="text-[11px] font-bold text-primary bg-primary-subtle px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                                        {{ $folder['unread_count'] > 99 ? '99+' : $folder['unread_count'] }}
                                    </span>
                                @endif
                                @if($folder['total_count'] > 0 && $folder['unread_count'] === 0)
                                    <span class="text-[11px] text-ink-tertiary">{{ $folder['total_count'] }}</span>
                                @endif
                            </div>

                            {{-- Expand/collapse for folders with children --}}
                            @if($folder['has_children'])
                                <button
                                    class="ml-1 text-ink-tertiary hover:text-ink p-0.5 rounded"
                                    @click.stop="expandedFolders['{{ $folder['path'] }}'] = !expandedFolders['{{ $folder['path'] }}']"
                                >
                                    <svg class="w-3.5 h-3.5 transition-transform duration-150"
                                         :class="{ 'rotate-90': expandedFolders['{{ $folder['path'] }}'] }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        {{-- Child folders --}}
                        @if($folder['has_children'] && $expandedFolders[$folder['path']] ?? false)
                            <div class="ml-5 border-l border-border-subtle mt-0.5 mb-1 space-y-0.5">
                                {{-- Child folders placeholder --}}
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>

            {{-- Custom folders separator --}}
            @if(array_filter($folders, fn($f) => !$f['role']))
                <div class="mt-2 pt-3 border-t border-border-subtle px-2">
                    <div class="px-3 py-1.5 text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider">
                        Custom Folders
                    </div>
                    <div class="space-y-0.5">
                        @foreach($folders as $folder)
                            @if(!$folder['role'])
                                <div
                                    class="group flex items-center px-3 py-2 rounded-lg cursor-pointer transition-all duration-100
                                        {{ $folder['path'] === $currentFolder
                                            ? 'bg-primary-subtle text-primary font-medium'
                                            : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                                    wire:click="selectFolder('{{ $folder['path'] }}')"
                                >
                                    <svg class="w-[18px] h-[18px] mr-3 flex-shrink-0 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                                    </svg>
                                    <span class="flex-1 truncate text-sm">{{ $folder['name'] }}</span>
                                    <div class="flex items-center gap-1.5">
                                        @if($folder['unread_count'] > 0)
                                            <span class="text-[11px] font-bold text-primary bg-primary-subtle px-1.5 py-0.5 rounded-full">
                                                {{ $folder['unread_count'] }}
                                            </span>
                                        @endif
                                        @if($folder['total_count'] > 0 && $folder['unread_count'] === 0)
                                            <span class="text-[11px] text-ink-tertiary">{{ $folder['total_count'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Refresh button --}}
            <div class="mt-auto p-3 border-t border-border-subtle">
                <button
                    wire:click="refreshFolders"
                    data-loading.attr="disabled"
                    class="w-full text-xs text-ink-tertiary hover:text-ink flex items-center justify-center gap-1.5 py-2 rounded-lg hover:bg-hover transition-colors"
                >
                    <svg class="w-3.5 h-3.5" data-loading remove wire:target="refreshFolders" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182"/>
                    </svg>
                    <span data-loading remove wire:target="refreshFolders">Refresh folders</span>
                    <span data-loading wire:target="refreshFolders">Refreshing...</span>
                </button>
            </div>
        </div>

        {{-- Contacts Panel --}}
        <div x-show="activeTab === 'contacts'" x-cloak>
            <livewire:mailbox.contact-sidebar :activeTab="$activeTab === 'contacts'" wire:key="contact-sidebar" />
        </div>

        {{-- Labels Panel --}}
        <div x-show="activeTab === 'labels'" x-cloak>
            <livewire:mailbox.label-sidebar :activeTab="$activeTab === 'labels'" wire:key="label-sidebar" />
        </div>
    </div>

    {{-- Mobile Bottom Navigation --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-surface-raised border-t border-border"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex">
            <button
                @click="$wire.setActiveTab('folders')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'folders' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
                <span class="text-[10px] mt-1 font-medium">Folders</span>
            </button>
            <button
                @click="$wire.setActiveTab('contacts')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'contacts' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                <span class="text-[10px] mt-1 font-medium">Contacts</span>
            </button>
            <button
                @click="$wire.setActiveTab('labels')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'labels' ? 'text-primary' : 'text-ink-tertiary' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
                </svg>
                <span class="text-[10px] mt-1 font-medium">Labels</span>
            </button>
        </div>
    </div>
</div>
