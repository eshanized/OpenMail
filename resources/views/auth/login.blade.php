<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenMail') }} — Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script @cspNonceAttribute>
        (function() {
            var theme = localStorage.getItem('theme') || 'system';
            var density = localStorage.getItem('density') || 'regular';
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || (theme === 'system' && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
            document.documentElement.classList.add('density-' + density);
        })();
    </script>
</head>
<body class="bg-surface min-h-screen antialiased">
    <div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-[400px] px-4">
            {{-- Logo --}}
            <div class="flex justify-center mb-6">
                <div class="w-12 h-12 bg-primary rounded-xl flex items-center justify-center shadow-md">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>

            {{-- Title --}}
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-ink tracking-tight">
                    {{ config('app.name', 'OpenMail') }}
                </h1>
                <p class="mt-2 text-sm text-ink-secondary">
                    Sign in to access your mailbox
                </p>
            </div>

            {{-- Login Card --}}
            <div class="bg-surface-raised rounded-xl border border-border shadow-sm p-6 sm:p-8">
                @livewire('login-form')
            </div>

            {{-- Footer --}}
            <p class="mt-6 text-center text-xs text-ink-tertiary">
                Self-hosted webmail by
                <span class="font-medium text-ink-secondary">Tonmoy Infrastructure</span>
            </p>
        </div>
    </div>
</body>
</html>
