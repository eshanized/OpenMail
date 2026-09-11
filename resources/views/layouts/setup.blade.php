<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenMail &mdash; Setup</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Inline theme initializer --}}
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('theme') || 'system';
                var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="setup-bg text-ink min-h-screen flex flex-col antialiased">
    {{-- Header --}}
    <header class="setup-header sticky top-0 z-40">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 h-12 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <svg class="w-4.5 h-4.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <div class="flex items-baseline gap-2">
                    <span class="text-sm font-semibold text-ink tracking-tight">OpenMail</span>
                    <span class="text-xs text-ink-tertiary">Setup</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                {{-- Theme toggle --}}
                <button type="button"
                        onclick="(function(){
                            var isDark = document.documentElement.classList.toggle('dark');
                            localStorage.setItem('theme', isDark ? 'dark' : 'light');
                            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: isDark ? 'dark' : 'light' } }));
                        })()"
                        title="Toggle color scheme"
                        class="p-1 rounded text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                        aria-label="Toggle dark mode">
                    <svg class="w-4 h-4 hidden dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <svg class="w-4 h-4 block dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                    </svg>
                </button>

                <span class="text-[11px] text-ink-tertiary font-mono">
                    v1.0.0
                </span>
            </div>
        </div>
    </header>

    {{-- Main Container --}}
    <main class="flex-1 max-w-3xl w-full mx-auto py-8 px-4 sm:px-6">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="mt-auto border-t border-border py-4 text-center text-xs text-ink-tertiary">
        <div class="max-w-3xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <p>
                OpenMail &middot; Self-hosted webmail
            </p>
            <p class="text-ink-tertiary">
                Tonmoy Infrastructure
            </p>
        </div>
    </footer>
</body>
</html>
