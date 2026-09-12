<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenMail') }} - {{ $title ?? 'Mailbox' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')

    {{-- CSP nonce meta tag for inline scripts --}}
    @cspNonceMetaTag

    {{-- Inline theme initializer to prevent flash of wrong theme --}}
    <script @cspNonceAttribute>
        (function() {
            @auth
                @php
                    $savedTheme = auth()->user()->setting('theme', null);
                    $savedDensity = auth()->user()->setting('density', null);
                @endphp
                @if($savedTheme)
                    localStorage.setItem('theme', @js($savedTheme));
                @endif
                @if($savedDensity)
                    localStorage.setItem('density', @js($savedDensity));
                @endif
            @endauth
            var theme = localStorage.getItem('theme') || 'system';
            var density = localStorage.getItem('density') || 'regular';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }

            document.documentElement.classList.remove('density-compact', 'density-regular', 'density-comfortable');
            document.documentElement.classList.add('density-' + density);
            document.documentElement.dataset.themeInitialized = 'true';
        })();
    </script>
</head>
<body class="bg-surface text-ink min-h-screen antialiased">
    {{-- Route overlay fade cross --}}
    <div id="route-overlay"
         class="pointer-events-none fixed inset-0 z-[9999] opacity-0 transition-opacity duration-150"
         style="background-color: var(--color-surface)"
         aria-hidden="true"></div>

    {{-- Application header — minimal, recedes behind content --}}
    <nav class="bg-surface-raised border-b border-border sticky top-0 z-30">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-11 items-center">
                {{-- Left: Brand & Navigation --}}
                <div class="flex items-center gap-2 sm:gap-3">
                    @hasSection('mobile-nav-toggle')
                        @yield('mobile-nav-toggle')
                    @endif
                    <a href="{{ route('mailbox') }}" wire:navigate class="flex items-center gap-2 group">
                        {{-- Logo mark — simple envelope, no color background --}}
                        <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm font-semibold text-ink tracking-tight group-hover:text-primary transition-colors duration-150">
                            {{ config('app.name', 'OpenMail') }}
                        </span>
                    </a>
                </div>

                {{-- Right: Actions —  quiet, subordinate to the workspace --}}
                <div class="flex items-center gap-0.5">
                    @if (session('success'))
                        <span class="text-xs text-success mr-3">
                            {{ session('success') }}
                        </span>
                    @endif
                    @if (session('error'))
                        <span class="text-xs text-error mr-3">
                            {{ session('error') }}
                        </span>
                    @endif

                    <a href="{{ route('settings') }}"
                       title="Settings"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-secondary hover:text-ink px-2 py-1.5 rounded hover:bg-hover transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary/30"
                       wire:navigate>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="hidden md:inline">Settings</span>
                    </a>

                    <div class="w-px h-4 bg-border mx-1 hidden sm:block"></div>

                    {{-- User Profile — compact pill, no gradient avatar --}}
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button @click="open = !open" @click.outside="open = false"
                                type="button"
                                id="user-menu-button"
                                aria-expanded="open"
                                aria-haspopup="true"
                                class="inline-flex items-center gap-1.5 text-xs py-1 px-1.5 sm:px-2 rounded border transition-colors duration-100 cursor-pointer select-none focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary/30"
                                :class="open ? 'bg-surface-sunken border-border-strong text-ink' : 'bg-surface border-border text-ink-secondary hover:text-ink hover:bg-hover hover:border-border-strong'">
                            {{-- Avatar — neutral monogram, no gradient --}}
                            <div class="w-5.5 h-5.5 rounded-full bg-surface-sunken border border-border text-ink-secondary flex items-center justify-center text-[10px] font-semibold shrink-0">
                                {{ strtoupper(substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                            </div>
                            {{-- Truncated User Email/Name --}}
                            <span class="hidden sm:inline font-medium max-w-[120px] md:max-w-[160px] truncate text-ink">
                                {{ auth()->user()->name ?: auth()->user()->email }}
                            </span>
                            {{-- Chevron --}}
                            <svg class="w-3 h-3 text-ink-tertiary transition-transform duration-150 shrink-0"
                                 :class="{ 'rotate-180': open }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Dropdown Menu — clean, no rounded-2xl --}}
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-98 -translate-y-1"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-98 -translate-y-1"
                             class="absolute right-0 mt-1.5 w-64 sm:w-72 bg-surface-raised rounded-md shadow-lg border border-border p-1 z-50"
                             role="menu"
                             aria-orientation="vertical"
                             aria-labelledby="user-menu-button">

                            {{-- User identity — clean, no card --}}
                            <div class="px-3 py-2.5 border-b border-border mb-1">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-primary-subtle text-primary font-semibold text-sm flex items-center justify-center shrink-0 border border-primary/20">
                                        {{ strtoupper(substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-ink truncate leading-tight">
                                            {{ auth()->user()->name ?: Str::before(auth()->user()->email, '@') }}
                                        </p>
                                        <p class="text-xs text-ink-tertiary font-mono truncate mt-0.5" title="{{ auth()->user()->email }}">
                                            {{ auth()->user()->email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Menu Links --}}
                            <a href="{{ route('settings') }}" wire:navigate @click="open = false"
                               class="flex items-center gap-2.5 px-3 py-2 text-sm text-ink-secondary hover:text-ink hover:bg-hover rounded transition-colors"
                               role="menuitem">
                                <svg class="w-4 h-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Settings & Preferences
                            </a>

                            <a href="{{ route('settings') }}?tab=appearance" wire:navigate @click="open = false"
                               class="flex items-center gap-2.5 px-3 py-2 text-sm text-ink-secondary hover:text-ink hover:bg-hover rounded transition-colors"
                               role="menuitem">
                                <svg class="w-4 h-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                </svg>
                                Theme & Display
                            </a>

                            {{-- Divider --}}
                            <div class="border-t border-border my-1"></div>

                            {{-- Sign out --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-2.5 w-full px-3 py-2 text-sm text-error hover:bg-error-subtle rounded transition-colors cursor-pointer"
                                        role="menuitem">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                    </svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main id="app-main" class="flex-1 max-w-screen-2xl mx-auto py-6 px-4 sm:px-6 lg:px-8 w-full animate-fade-in">
        @yield('content')
        {{ $slot ?? '' }}
    </main>

    {{-- Global Toast Notifications --}}
    <div
        x-data="{
            toasts: [],
            add(message, type = 'info') {
                if (!message) return;
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message, type });
                setTimeout(() => this.remove(id), 5000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @openmail-toast.window="add($event.detail.message, $event.detail.type)"
        x-init="
            document.addEventListener('livewire:init', () => {
                Livewire.on('toast', (event) => {
                    let msg = typeof event === 'string' ? event : (event.message || event[0]);
                    let type = typeof event === 'object' && event.type ? event.type : (event[1] || 'info');
                    add(msg, type);
                });
            });
        "
        class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 pointer-events-none max-w-sm w-full"
    >
        <template x-for="t in toasts" :key="t.id">
            <div
                class="pointer-events-auto flex items-center justify-between gap-3 px-4 py-3 rounded-lg border shadow-lg text-xs font-medium transition-all"
                :class="{
                    'bg-surface-raised border-border text-ink': t.type === 'info',
                    'bg-emerald-500/10 border-emerald-500/20 text-emerald-600 dark:text-emerald-400': t.type === 'success',
                    'bg-amber-500/10 border-amber-500/20 text-amber-600 dark:text-amber-400': t.type === 'warning',
                    'bg-rose-500/10 border-rose-500/20 text-rose-600 dark:text-rose-400': t.type === 'error'
                }"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                <span x-text="t.message" class="flex-1"></span>
                <button @click="remove(t.id)" class="text-ink-tertiary hover:text-ink cursor-pointer">&times;</button>
            </div>
        </template>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
