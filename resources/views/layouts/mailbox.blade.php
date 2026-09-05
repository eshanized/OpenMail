@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-4rem)] flex overflow-hidden">
    {{-- Sidebar --}}
    <aside class="w-64 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col hidden lg:flex">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Folders</h2>
        </div>
        <div class="flex-1 overflow-y-auto">
            {{ $sidebar ?? '' }}
        </div>
    </aside>

    {{-- Mobile sidebar toggle --}}
    <button
        class="lg:hidden fixed top-20 left-4 z-50 bg-white p-2 rounded shadow-md"
        @click="$dispatch('toggle-sidebar')"
        aria-label="Toggle sidebar"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Mobile sidebar overlay --}}
    <div
        x-data="{ open: false }"
        @toggle-sidebar.window="open = !open"
        @keydown.escape.window="open = false"
        x-show="open"
        class="lg:hidden fixed inset-0 z-40 bg-black/50"
        @click="open = false"
    >
        <div class="fixed inset-y-0 left-0 w-64 bg-white shadow-xl" @click.outside="open = false">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Folders</h2>
                <button @click="open = false" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-4">
                {{ $sidebar ?? '' }}
            </div>
        </div>
    </div>

    {{-- Main content area --}}
    <main class="flex-1 flex flex-col min-w-0">
        <div class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </div>
    </main>
</div>

@push('scripts')
    @livewireScripts
@endpush