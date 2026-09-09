<div class="p-8 sm:p-14 text-center">
    {{-- Animated Success Badge --}}
    <div class="inline-flex p-4 rounded-3xl bg-emerald-500/10 border border-emerald-500/20 mb-6 shadow-inner">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-emerald-600 via-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <svg class="w-9 h-9 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
    </div>

    <h2 class="text-3xl font-extrabold text-ink tracking-tight">
        OpenMail is Ready!
    </h2>
    <p class="mt-2 text-base text-ink-secondary max-w-md mx-auto leading-relaxed">
        Your organization's webmail environment has been successfully configured and locked for security.
    </p>

    {{-- Setup Confirmation Panel --}}
    <div class="mt-8 p-5 setup-panel max-w-md mx-auto text-left space-y-3">
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Platform Version:</span>
            <span class="font-mono font-semibold text-ink">v1.0.0</span>
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Installation Status:</span>
            <span class="inline-flex items-center gap-1 font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Locked &amp; Active
            </span>
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Security Mode:</span>
            <span class="font-semibold text-ink">Hardened Session Cookies</span>
        </div>
    </div>

    <div class="mt-8 flex justify-center">
        <a href="{{ route('login') }}"
           class="setup-btn-primary !px-8 !py-3 text-base inline-flex items-center gap-2">
            <span>Proceed to Login</span>
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
            </svg>
        </a>
    </div>
</div>
