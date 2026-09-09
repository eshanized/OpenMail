@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-3.5rem)] flex overflow-hidden -mx-4 sm:-mx-6 lg:-mx-8 -my-6"
     x-data="{
         sidebarOpen: false,
     }">

    {{-- Desktop Sidebar --}}
    <aside class="w-[260px] flex-shrink-0 bg-surface-raised border-r border-border flex flex-col hidden lg:flex">
        <div class="flex-1 overflow-y-auto scrollbar-thin">
            @yield('sidebar')
            @isset($sidebar)
                {{ $sidebar }}
            @endisset
        </div>
    </aside>

    {{-- Mobile sidebar toggle --}}
    <button
        class="lg:hidden fixed top-16 left-3 z-50 bg-surface-raised border border-border p-2 rounded-lg shadow-sm"
        @click="sidebarOpen = !sidebarOpen"
        aria-label="Toggle navigation"
    >
        <svg class="w-5 h-5 text-ink" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- Mobile sidebar overlay --}}
    <div
        x-show="sidebarOpen"
        x-cloak
        class="lg:hidden fixed inset-0 z-50 bg-black/40"
        @click="sidebarOpen = false"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="fixed inset-y-0 left-0 w-[280px] bg-surface-raised shadow-xl" @click.outside="sidebarOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full">
            <div class="h-14 px-4 border-b border-border flex justify-between items-center">
                <span class="text-sm font-semibold text-ink">{{ config('app.name', 'OpenMail') }}</span>
                <button @click="sidebarOpen = false" class="p-1.5 text-ink-tertiary hover:text-ink rounded-md hover:bg-hover transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto scrollbar-thin">
                @yield('sidebar')
                @isset($sidebar)
                    {{ $sidebar }}
                @endisset
            </div>
        </div>
    </div>

    {{-- Main content area --}}
    <main class="flex-1 flex flex-col min-w-0 bg-surface">
        <div class="flex-1 overflow-y-auto scrollbar-thin p-4 sm:p-6 pb-20 md:pb-6">
            @yield('mailbox-content')
            {{ $slot ?? '' }}
        </div>
    </main>

    {{-- Mobile bottom padding for bottom nav --}}
    <div class="md:hidden h-16"></div>
</div>
@endsection
