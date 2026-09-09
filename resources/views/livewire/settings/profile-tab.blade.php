<div class="space-y-8">
    {{-- Hero Profile Card --}}
    @php
        $userName = auth()->user()->name ?: Str::before(auth()->user()->email, '@');
        $userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $userName) ?: 'U', 0, 1));
        $avatarIdx = abs(crc32(auth()->user()->email ?? 'user')) % 6;
    @endphp
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-surface-sunken/60 rounded-2xl border border-border-subtle">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-2xl avatar-gradient-{{ $avatarIdx }} flex items-center justify-center text-xl font-bold shadow-sm shrink-0 ring-4 ring-surface-raised">
                {{ $userInitial }}
            </div>
            <div>
                <h3 class="text-base font-bold text-ink">{{ $name ?: $userName }}</h3>
                <p class="text-xs text-ink-tertiary font-mono mt-0.5">{{ $email ?: auth()->user()->email }}</p>
                <div class="flex items-center gap-2 mt-2">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        Active Mailbox
                    </span>
                    <span class="text-xs text-ink-tertiary">·</span>
                    <span class="text-[11px] text-ink-secondary">Tonmoy Infrastructure</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Form Fields --}}
    <div class="space-y-5">
        {{-- Display Name --}}
        <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">
                Display Name
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-tertiary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    id="name"
                    wire:model="name"
                    placeholder="Enter your full name"
                    class="settings-input pl-9.5 text-sm"
                >
            </div>
            <p class="text-xs text-ink-tertiary mt-1.5">Your name displayed as the sender on outgoing emails.</p>
            @error('name') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Email Address --}}
        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">
                Email Address
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-tertiary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    placeholder="user@example.com"
                    class="settings-input pl-9.5 text-sm"
                >
            </div>
            <p class="text-xs text-ink-tertiary mt-1.5">Your primary login account and reply-to address.</p>
            @error('email') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Infrastructure Info Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-4 border-t border-border-subtle">
        <div class="p-3.5 bg-surface-sunken/50 rounded-xl border border-border-subtle">
            <span class="text-[11px] font-semibold text-ink-tertiary uppercase tracking-wider block">Protocol</span>
            <span class="text-xs font-semibold text-ink mt-1 block">IMAP & SMTP</span>
            <span class="text-[10px] text-ink-tertiary mt-0.5 block">Standard mail sync</span>
        </div>
        <div class="p-3.5 bg-surface-sunken/50 rounded-xl border border-border-subtle">
            <span class="text-[11px] font-semibold text-ink-tertiary uppercase tracking-wider block">Encryption</span>
            <span class="text-xs font-semibold text-ink mt-1 block">TLS / SSL</span>
            <span class="text-[10px] text-ink-tertiary mt-0.5 block">End-to-end transport</span>
        </div>
        <div class="p-3.5 bg-surface-sunken/50 rounded-xl border border-border-subtle">
            <span class="text-[11px] font-semibold text-ink-tertiary uppercase tracking-wider block">Mail Storage</span>
            <span class="text-xs font-semibold text-ink mt-1 block">Mail Server (Source)</span>
            <span class="text-[10px] text-ink-tertiary mt-0.5 block">Zero vendor lock-in</span>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="pt-3 flex items-center justify-end">
        <button
            wire:click="save"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
        >
            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-1 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Save Profile Changes</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </div>
</div>