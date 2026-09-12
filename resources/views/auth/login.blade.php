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
            } else {
                document.documentElement.classList.remove('dark');
            }
            document.documentElement.classList.add('density-' + density);
            document.documentElement.dataset.themeInitialized = 'true';
        })();
    </script>
</head>
<body class="bg-surface min-h-screen antialiased">

    {{--
        Login page — editorial, calm, trustworthy
        NOT: giant black background, glowing blue logo, oversized card
        Design: clean two-section layout, warm light background, tight typography
    --}}

    <div class="min-h-screen flex">
        {{-- Left panel: branding + identity --}}
        <div class="hidden lg:flex lg:w-5/12 xl:w-1/2 flex-col justify-between bg-surface-sunken border-r border-border p-10 xl:p-14">
            {{-- Logo --}}
            <div class="flex items-center gap-2.5">
                <svg class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-semibold text-ink tracking-tight">{{ config('app.name', 'OpenMail') }}</span>
            </div>

            {{-- Main editorial statement --}}
            <div>
                <h1 class="text-3xl xl:text-4xl font-bold text-ink tracking-tight leading-snug mb-4">
                    Company email,<br>without the clutter.
                </h1>
                <p class="text-base text-ink-secondary leading-relaxed max-w-sm">
                    A focused, professional webmail experience built for serious work. Connect to your existing mail infrastructure.
                </p>

                {{-- Feature points — clean, no icons --}}
                <div class="mt-8 space-y-2.5">
                    @foreach(['IMAP & SMTP compatible', 'Self-hosted, no data sharing', 'Keyboard-first workflow', 'Thread and label support'] as $feature)
                        <div class="flex items-center gap-2.5 text-sm text-ink-secondary">
                            <span class="w-1 h-1 rounded-full bg-ink-tertiary flex-shrink-0"></span>
                            <span>{{ $feature }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Attribution — subtle --}}
            <p class="text-xs text-ink-tertiary">
                OpenMail by <span class="text-ink-secondary">Tonmoy Infrastructure</span>
            </p>
        </div>

        {{-- Right panel: sign in form --}}
        <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-16 xl:px-20">
            {{-- Mobile logo (hidden on lg+) --}}
            <div class="flex items-center gap-2 mb-10 lg:hidden">
                <svg class="w-4.5 h-4.5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="text-sm font-semibold text-ink">{{ config('app.name', 'OpenMail') }}</span>
            </div>

            <div class="w-full max-w-sm bg-surface-raised sm:border sm:border-border sm:p-6 rounded sm:shadow-xs">
                {{-- Heading --}}
                <div class="mb-5">
                    <h2 class="text-xl font-semibold text-ink tracking-tight">
                        Sign in
                    </h2>
                    <p class="mt-1 text-xs text-ink-secondary">
                        Access your mailbox
                    </p>
                </div>

                {{-- Form --}}
                @livewire('login-form')

                {{-- Footer --}}
                <p class="mt-8 text-xs text-ink-tertiary text-center lg:text-left">
                    Secure company webmail &middot;
                    <span class="text-ink-secondary">Tonmoy Infrastructure</span>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
