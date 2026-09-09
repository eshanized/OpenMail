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
                    if (!localStorage.getItem('theme')) {
                        localStorage.setItem('theme', @js($savedTheme));
                    }
                @endif
                @if($savedDensity)
                    if (!localStorage.getItem('density')) {
                        localStorage.setItem('density', @js($savedDensity));
                    }
                @endif
            @endauth
            var theme = localStorage.getItem('theme') || 'system';
            var density = localStorage.getItem('density') || 'regular';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            }

            document.documentElement.classList.remove('density-compact', 'density-regular', 'density-comfortable');
            document.documentElement.classList.add('density-' + density);
        })();
    </script>
</head>
<body class="bg-surface text-ink min-h-screen antialiased">
    {{-- Route overlay fade cross --}}
    <div id="route-overlay"
         class="pointer-events-none fixed inset-0 z-[9999] opacity-0 transition-opacity duration-200"
         style="background-color: var(--color-surface)"
         aria-hidden="true"></div>

    <nav class="bg-surface-raised border-b border-border sticky top-0 z-30">
        <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-14 items-center">
                {{-- Left: Brand --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('mailbox') }}" wire:navigate class="flex items-center gap-2.5 group">
                        <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-base font-bold text-ink tracking-tight group-hover:text-primary transition-colors">
                            {{ config('app.name', 'OpenMail') }}
                        </span>
                    </a>
                </div>

                {{-- Right: Actions --}}
                <div class="flex items-center gap-1">
                    @if (session('success'))
                        <span class="text-xs font-medium text-success bg-success-subtle px-2.5 py-1 rounded-full mr-2">
                            {{ session('success') }}
                        </span>
                    @endif
                    @if (session('error'))
                        <span class="text-xs font-medium text-error bg-error-subtle px-2.5 py-1 rounded-full mr-2">
                            {{ session('error') }}
                        </span>
                    @endif

                    <a href="{{ route('settings') }}"
                       title="Settings"
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-ink-secondary hover:text-ink px-2.5 py-1.5 rounded-lg hover:bg-hover transition-colors focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary/40"
                       wire:navigate>
                        <svg class="w-4 h-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="hidden md:inline">Settings</span>
                    </a>

                    <div class="w-px h-5 bg-border mx-1.5 hidden sm:block"></div>

                    {{-- User Profile Pill & Dropdown --}}
                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
                        <button @click="open = !open" @click.outside="open = false"
                                type="button"
                                id="user-menu-button"
                                aria-expanded="open"
                                aria-haspopup="true"
                                class="inline-flex items-center gap-2 text-xs py-1 px-1.5 sm:px-2 rounded-full border transition-all duration-150 cursor-pointer select-none focus:outline-hidden focus-visible:ring-2 focus-visible:ring-primary/40 focus-visible:ring-offset-1 focus-visible:ring-offset-surface-raised"
                                :class="open ? 'bg-surface-sunken border-border-strong text-ink shadow-2xs' : 'bg-surface border-border/70 text-ink-secondary hover:text-ink hover:bg-hover hover:border-border'">
                            {{-- Avatar Initial with subtle gradient --}}
                            <div class="w-6.5 h-6.5 rounded-full bg-linear-to-tr from-primary via-indigo-500 to-sky-400 text-white flex items-center justify-center text-xs font-bold shadow-xs shrink-0 ring-1 ring-white/20">
                                {{ strtoupper(substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                            </div>
                            {{-- Truncated User Email/Name --}}
                            <span class="hidden sm:inline font-medium max-w-[140px] md:max-w-[190px] truncate text-ink">
                                {{ auth()->user()->name ?: auth()->user()->email }}
                            </span>
                            {{-- Chevron --}}
                            <svg class="w-3.5 h-3.5 text-ink-tertiary transition-transform duration-200 shrink-0 pr-0.5"
                                 :class="{ 'rotate-180 text-primary': open }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Modern Popover Menu --}}
                        <div x-show="open" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="transform opacity-0 scale-95 -translate-y-1"
                             x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="transform opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="transform opacity-0 scale-95 -translate-y-1"
                             class="absolute right-0 mt-2 w-72 sm:w-80 bg-surface-raised/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-border p-2 z-50 ring-1 ring-black/5 dark:ring-white/10"
                             role="menu"
                             aria-orientation="vertical"
                             aria-labelledby="user-menu-button">

                            {{-- User Header Card --}}
                            <div class="p-3 bg-surface-sunken/60 rounded-xl border border-border-subtle mb-1.5">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-linear-to-tr from-primary via-indigo-500 to-sky-400 text-white font-bold text-sm flex items-center justify-center shadow-sm shrink-0 ring-1 ring-white/20">
                                        {{ strtoupper(substr(auth()->user()->name ?: auth()->user()->email, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-1">
                                            <p class="text-sm font-semibold text-ink truncate leading-tight">
                                                {{ auth()->user()->name ?: Str::before(auth()->user()->email, '@') }}
                                            </p>
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 shrink-0">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        </div>
                                        <p class="text-xs text-ink-tertiary font-mono break-all mt-0.5 select-all leading-normal" title="{{ auth()->user()->email }}">
                                            {{ auth()->user()->email }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Menu Links --}}
                            <div class="space-y-0.5">
                                <a href="{{ route('settings') }}" wire:navigate @click="open = false"
                                   class="flex items-center justify-between px-3 py-2 text-xs font-medium text-ink-secondary hover:text-ink hover:bg-hover rounded-lg transition-colors group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-7 h-7 rounded-md bg-surface-sunken flex items-center justify-center text-ink-tertiary group-hover:text-primary transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-ink font-medium">Settings & Preferences</span>
                                            <p class="text-[10px] text-ink-tertiary">Profile, mail filters & signatures</p>
                                        </div>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-ink-tertiary group-hover:text-ink group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </a>

                                <a href="{{ route('settings') }}?tab=appearance" wire:navigate @click="open = false"
                                   class="flex items-center justify-between px-3 py-2 text-xs font-medium text-ink-secondary hover:text-ink hover:bg-hover rounded-lg transition-colors group">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="w-7 h-7 rounded-md bg-surface-sunken flex items-center justify-center text-ink-tertiary group-hover:text-primary transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <span class="text-ink font-medium">Theme & Display</span>
                                            <p class="text-[10px] text-ink-tertiary">Light, dark or system theme</p>
                                        </div>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-ink-tertiary group-hover:text-ink group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                                    </svg>
                                </a>
                            </div>

                            {{-- Divider --}}
                            <div class="border-t border-border-subtle my-1.5"></div>

                            {{-- Sign out --}}
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center justify-between w-full px-3 py-2 text-xs font-medium text-error hover:bg-error-subtle rounded-lg transition-colors cursor-pointer group">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 rounded-md bg-error-subtle/70 flex items-center justify-center text-error group-hover:scale-105 transition-transform">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                                            </svg>
                                        </div>
                                        <span>Sign out</span>
                                    </div>
                                    <span class="text-[10px] text-error/60 font-mono">Log out</span>
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

    @livewireScripts
    @stack('scripts')
</body>
</html>
