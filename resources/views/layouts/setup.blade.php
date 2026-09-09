<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OpenMail Setup Wizard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="bg-white border-b border-gray-200 shadow-sm">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-gray-900">OpenMail</span>
                    <span class="text-sm text-gray-500 hidden sm:inline">Setup Wizard</span>
                </div>
                <span class="text-xs text-gray-400">v1.0.0</span>
            </div>
        </header>

        {{-- Main --}}
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                @yield('content')
            </div>
        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-200 py-4 text-center text-xs text-gray-400">
            OpenMail &mdash; Self-hosted webmail for organizations
        </footer>
    </div>
</body>
</html>