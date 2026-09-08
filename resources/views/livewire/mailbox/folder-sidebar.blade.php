<div x-data="{ expandedFolders: {}, activeTab: @js($activeTab) }"
     @set-active-tab.window="activeTab = $event.detail.tab"
     x-init="
         // Initialize from Livewire state
         $wire.on('active-tab-changed', (tab) => { activeTab = tab; });
     "
     class="h-full flex flex-col glass-card backdrop-blur-sm">

    {{-- Desktop Tab Bar (≥768px) --}}
    <div class="hidden md:flex border-b border-white/60 dark:border-white/10">
        <button
            @click="$wire.setActiveTab('folders')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-sm font-medium transition-colors
                {{ $activeTab === 'folders'
                    ? 'text-primary bg-white/60 dark:bg-white/10 border-b-2 border-primary'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-white/60 dark:hover:bg-white/10' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
            Folders
        </button>
        <button
            @click="$wire.setActiveTab('contacts')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-sm font-medium transition-colors
                {{ $activeTab === 'contacts'
                    ? 'text-primary bg-white/60 dark:bg-white/10 border-b-2 border-primary'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-white/60 dark:hover:bg-white/10' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Contacts
        </button>
        <button
            @click="$wire.setActiveTab('labels')"
            class="flex-1 flex items-center justify-center gap-1.5 px-3 py-3 text-sm font-medium transition-colors
                {{ $activeTab === 'labels'
                    ? 'text-primary bg-white/60 dark:bg-white/10 border-b-2 border-primary'
                    : 'text-gray-500 hover:text-gray-700 hover:bg-white/60 dark:hover:bg-white/10' }}"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Labels
        </button>
    </div>

    {{-- Tab Panels --}}
    <div class="flex-1 overflow-y-auto">
        {{-- Folders Panel --}}
        <div x-show="activeTab === 'folders'" x-cloak>
            {{-- Standard folders --}}
            <div class="space-y-1 p-2">
                @foreach($folders as $folder)
                    @if($folder['role'])
                        <div
                            class="flex items-center px-3 py-2 rounded-lg cursor-pointer transition-colors
                                {{ $folder['path'] === $currentFolder
                                    ? 'bg-white/60 dark:bg-white/10 border-l-2 border-primary text-primary'
                                    : 'text-ink hover:bg-white/60 dark:hover:bg-white/10' }}"
                            wire:click="selectFolder('{{ $folder['path'] }}')"
                        >
                            {{-- Folder icon based on role --}}
                            <span class="w-5 h-5 mr-3 flex-shrink-0">
                                @switch($folder['role'])
                                    @case('inbox')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path></svg>
                                        @break
                                    @case('sent')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.949l7.582-1.516a1 1 0 00.618 0l7.582 1.516a1 1 0 001.169-1.949l-7-14zM6.707 10.293a1 1 0 011.414 0L10 11.586l1.879-1.88a1 1 0 111.414 1.414l-2.5 2.5a1 1 0 01-1.414 0l-2.5-2.5a1 1 0 010-1.414z"></path></svg>
                                        @break
                                    @case('drafts')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 3a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V3zm2 1a1 1 0 011-1h10a1 1 0 110 2H7a1 1 0 01-1-1zm0 4a1 1 0 100 2h10a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                                        @break
                                    @case('trash')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                        @break
                                    @case('spam')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                                        @break
                                    @case('archive')
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path></svg>
                                        @break
                                @endswitch
                            </span>

                            <span class="flex-1 truncate font-medium">
                                {{ $folder['name'] }}
                            </span>

                            {{-- Count badges --}}
                            <div class="flex items-center space-x-2">
                                @if($folder['unread_count'] > 0)
                                    <span class="text-xs font-semibold text-primary bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 rounded-full">
                                        {{ $folder['unread_count'] }}
                                    </span>
                                @endif
                                @if($folder['total_count'] > 0)
                                    <span class="text-xs text-gray-500">
                                        {{ $folder['total_count'] }}
                                    </span>
                                @endif
                            </div>

                            {{-- Expand/collapse for folders with children --}}
                            @if($folder['has_children'])
                                <button
                                    class="ml-2 text-gray-400 hover:text-gray-600"
                                    @click.stop="expandedFolders['{{ $folder['path'] }}'] = !expandedFolders['{{ $folder['path'] }}']"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            @endif
                        </div>

                        {{-- Child folders --}}
                        @if($folder['has_children'] && $expandedFolders[$folder['path']] ?? false)
                            <div class="ml-4 border-l border-white/60 dark:border-white/10 mt-1 space-y-1">
                                {{-- Child folders would be loaded here --}}
                            </div>
                        @endif
                    @endif
                @endforeach
            </div>

            {{-- Custom folders separator --}}
            @if(array_filter($folders, fn($f) => !$f['role']))
                <div class="mt-4 pt-4 border-t border-white/60 dark:border-white/10 px-2">
                    <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Folders
                    </div>
                    <div class="space-y-1">
                        @foreach($folders as $folder)
                            @if(!$folder['role'])
                                <div
                                    class="flex items-center px-3 py-2 rounded-lg cursor-pointer transition-colors
                                        {{ $folder['path'] === $currentFolder
                                            ? 'bg-white/60 dark:bg-white/10 border-l-2 border-primary text-primary'
                                            : 'text-ink hover:bg-white/60 dark:hover:bg-white/10' }}"
                                    wire:click="selectFolder('{{ $folder['path'] }}')"
                                >
                                    <svg class="w-5 h-5 mr-3 flex-shrink-0 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                    </svg>
                                    <span class="flex-1 truncate">{{ $folder['name'] }}</span>
                                    <div class="flex items-center space-x-2">
                                        @if($folder['unread_count'] > 0)
                                            <span class="text-xs font-semibold text-primary bg-blue-100 dark:bg-blue-900/30 px-2 py-0.5 rounded-full">
                                                {{ $folder['unread_count'] }}
                                            </span>
                                        @endif
                                        @if($folder['total_count'] > 0)
                                            <span class="text-xs text-gray-500">{{ $folder['total_count'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Refresh button --}}
            <div class="mt-4 p-4 border-t border-white/60 dark:border-white/10">
                <button
                    wire:click="refreshFolders"
                    wire:loading.attr="disabled"
                    class="w-full text-sm text-gray-600 hover:text-gray-900 flex items-center justify-center space-x-2"
                >
                    <svg class="w-4 h-4" wire:loading remove wire:target="refreshFolders" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span wire:loading remove wire:target="refreshFolders">Refresh</span>
                    <span wire:loading.attr="disabled" wire:target="refreshFolders">Refreshing...</span>
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

    {{-- Mobile Bottom Navigation (<768px) --}}
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200"
         style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="flex">
            <button
                @click="$wire.setActiveTab('folders')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'folders'
                        ? 'text-blue-600'
                        : 'text-gray-500' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                </svg>
                <span class="text-xs mt-1 font-medium">Folders</span>
            </button>
            <button
                @click="$wire.setActiveTab('contacts')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'contacts'
                        ? 'text-blue-600'
                        : 'text-gray-500' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-xs mt-1 font-medium">Contacts</span>
            </button>
            <button
                @click="$wire.setActiveTab('labels')"
                class="flex-1 flex flex-col items-center justify-center py-3 transition-colors
                    {{ $activeTab === 'labels'
                        ? 'text-blue-600'
                        : 'text-gray-500' }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <span class="text-xs mt-1 font-medium">Labels</span>
            </button>
        </div>
    </div>
</div>
