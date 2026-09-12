@extends('layouts.app')

@section('mobile-nav-toggle')
    <button
        type="button"
        class="lg:hidden p-1 -ml-1 text-ink-secondary hover:text-ink hover:bg-hover rounded transition-colors cursor-pointer"
        @click="$dispatch('toggle-sidebar')"
        aria-label="Toggle navigation menu"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>
@endsection

@section('content')
<div class="h-[calc(100vh-2.75rem)] min-h-[calc(100dvh-2.75rem)] flex overflow-hidden -mx-4 sm:-mx-6 lg:-mx-8 -my-6"
     x-data="{
         sidebarOpen: false,
     }"
     @toggle-sidebar.window="sidebarOpen = !sidebarOpen">

    {{-- Desktop Sidebar --}}
    <aside class="w-[240px] flex-shrink-0 bg-surface-raised border-r border-border flex flex-col hidden lg:flex h-full min-h-0">
        <div class="flex-1 flex flex-col min-h-0 overflow-hidden">
            @yield('sidebar')
            @isset($sidebar)
                {{ $sidebar }}
            @endisset
        </div>
    </aside>

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        class="lg:hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-xs"
        @click="sidebarOpen = false"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="fixed inset-y-0 left-0 w-[280px] max-w-[85vw] bg-surface-raised border-r border-border shadow-xl flex flex-col z-50"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            <div class="h-11 px-4 border-b border-border flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-sm font-semibold text-ink">{{ config('app.name', 'OpenMail') }}</span>
                </div>
                <button @click="sidebarOpen = false" class="p-1 text-ink-tertiary hover:text-ink rounded hover:bg-hover transition-colors cursor-pointer" aria-label="Close navigation">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 flex flex-col min-h-0 overflow-hidden" @click="if ($event.target.closest('[wire\\:click*=\'selectFolder\'], a')) sidebarOpen = false">
                @hasSection('mobile-sidebar')
                    @yield('mobile-sidebar')
                @else
                    @yield('sidebar')
                @endif
                @isset($sidebar)
                    {{ $sidebar }}
                @endisset
            </div>
        </div>
    </div>

    {{-- Main content area --}}
    <main class="flex-1 flex flex-col min-w-0 bg-surface">
        <div class="flex-1 overflow-y-auto scrollbar-thin p-3 sm:p-5">
            @yield('mailbox-content')
            {{ $slot ?? '' }}
        </div>
    </main>
</div>
@endsection
