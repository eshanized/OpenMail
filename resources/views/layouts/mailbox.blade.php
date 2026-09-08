@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-4rem)] flex overflow-hidden"
     x-data="{
         sidebarOpen: false,
         openPanel: null,
         initGlobalKeyboardShortcuts() {
             // '/' focuses search
             // 't' toggles thread mode
             // 'e' archives selected
             // These are handled by individual components
         }
     }"
     x-init="initGlobalKeyboardShortcuts()"
     :class="densityClass">

    {{-- Desktop Sidebar --}}
    <aside class="w-64 flex-shrink-0 glass-card border-r border-white/60 dark:border-white/10 flex flex-col hidden lg:flex">
        <div class="flex-1 overflow-y-auto">
            {{ $sidebar ?? '' }}
        </div>
    </aside>

    {{-- Mobile sidebar toggle --}}
    <button
        class="lg:hidden fixed top-20 left-4 z-50 glass-card p-2 rounded shadow-md"
        @click="sidebarOpen = !sidebarOpen"
        aria-label="Toggle sidebar"
    >
        <svg class="w-6 h-6 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        class="lg:hidden fixed inset-0 z-50 bg-black/50"
        @click="sidebarOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="fixed inset-y-0 left-0 w-64 glass-card shadow-xl" @click.outside="sidebarOpen = false">
            <div class="p-4 border-b border-white/60 dark:border-white/10 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-ink">OpenMail</h2>
                <button @click="sidebarOpen = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto">
                {{ $sidebar ?? '' }}
            </div>
        </div>
    </div>

    {{-- Main content area --}}
    <main class="flex-1 flex flex-col min-w-0">
        <div class="flex-1 overflow-y-auto p-6 pb-20 md:pb-6">
            @yield('content')
        </div>
    </main>

    {{-- Mobile bottom padding for bottom nav --}}
    <div class="md:hidden h-16"></div>
</div>

@push('scripts')
    @livewireScripts
@endpush
