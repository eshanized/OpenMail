<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenMail') }} - {{ $title ?? 'Mailbox' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- CSP nonce meta tag for inline scripts (spatie/laravel-csp injects automatically) --}}
    @cspNonceMetaTag

    {{-- Inline theme initializer to prevent flash of wrong theme on page load --}}
    <script @cspNonceAttribute nonce="{{ Vite::cspNonce() }}">
        (function() {
            var theme = localStorage.getItem('theme') || 'system';
            var density = localStorage.getItem('density') || 'regular';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            // Apply theme immediately
            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            }

            // Apply density immediately
            document.documentElement.classList.add('density-' + density);
        })();
    </script>
</head>
<body class="bg-surface text-ink min-h-screen flex flex-col">
    {{-- Route overlay fade cross (D-12) — fixed, invisible by default, high z-index --}}
    <div id="route-overlay"
         class="pointer-events-none fixed inset-0 z-[9999] opacity-0 transition-opacity duration-150"
         style="background-color: var(--route-overlay-bg)"
         aria-hidden="true"></div>

    {{-- Decorative blob layer for glass visibility (D-07, Pitfall 4) — fixed, pointer-events-none, z-0 --}}
    <div class="bg-blobs" aria-hidden="true"></div>

    <nav class="bg-surface-raised dark:bg-surface-raised shadow-sm border-b border-gray-200 dark:border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-ink">{{ config('app.name', 'OpenMail') }}</h1>
                </div>
                <div class="flex items-center space-x-4">
                    @if (session('success'))
                        <div class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</div>
                    @endif
                    <a href="{{ route('settings') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        Settings
                    </a>
                    <span class="text-sm text-ink">{{ auth()->user()->email }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <main id="app-main" class="flex-1 max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 w-full animate-fade-in">
        @yield('content')
    </main>
</body>
</html>