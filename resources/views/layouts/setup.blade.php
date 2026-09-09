<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenMail Setup</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface min-h-screen antialiased">
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-surface-raised border-b border-border">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold text-ink">OpenMail</span>
                    <span class="text-xs font-medium text-ink-tertiary bg-surface-sunken px-2 py-0.5 rounded-full">Setup</span>
                </div>
                <span class="text-xs text-ink-tertiary font-mono">v1.0.0</span>
            </div>
        </header>

        {{-- Main --}}
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl mx-auto">
                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-border py-4 text-center">
            <p class="text-xs text-ink-tertiary">
                OpenMail &mdash; Self-hosted webmail for organizations
                <span class="mx-1.5 text-border">·</span>
                Powered by Tonmoy Infrastructure
            </p>
        </footer>
    </div>
</body>
</html>
