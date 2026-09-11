<div class="space-y-6">
    {{-- Identity Overview --}}
    @php
        $userName = auth()->user()->name ?: Str::before(auth()->user()->email, '@');
        $userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $userName) ?: 'U', 0, 1));
        $avatarIdx = abs(crc32(auth()->user()->email ?? 'user')) % 6;
    @endphp
    <div class="flex items-center gap-3 p-3.5 bg-surface-sunken rounded border border-border">
        <div class="w-10 h-10 rounded avatar-gradient-{{ $avatarIdx }} flex items-center justify-center text-sm font-semibold shrink-0">
            {{ $userInitial }}
        </div>
        <div>
            <h3 class="text-sm font-semibold text-ink leading-tight">{{ $name ?: $userName }}</h3>
            <p class="text-xs text-ink-tertiary font-mono mt-0.5">{{ $email ?: auth()->user()->email }}</p>
        </div>
    </div>

    {{-- Form Fields --}}
    <div class="space-y-4">
        {{-- Display Name --}}
        <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1">
                Display Name
            </label>
            <input
                type="text"
                id="name"
                wire:model="name"
                placeholder="Your name"
                class="settings-input text-xs"
            >
            <p class="text-xs text-ink-tertiary mt-1">Sender name shown on outgoing mail.</p>
            @error('name') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
        </div>

        {{-- Email Address --}}
        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1">
                Email Address
            </label>
            <input
                type="email"
                id="email"
                wire:model="email"
                placeholder="user@example.com"
                class="settings-input text-xs"
            >
            <p class="text-xs text-ink-tertiary mt-1">Account login and reply-to address.</p>
            @error('email') <span class="text-xs text-error mt-1 block">{{ $message }}</span> @enderror
        </div>
    </div>

    {{-- Infrastructure Info --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-border">
        <div class="p-3 bg-surface-sunken rounded border border-border">
            <span class="text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider block">Protocol</span>
            <span class="text-xs font-medium text-ink mt-0.5 block">IMAP &amp; SMTP</span>
        </div>
        <div class="p-3 bg-surface-sunken rounded border border-border">
            <span class="text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider block">Encryption</span>
            <span class="text-xs font-medium text-ink mt-0.5 block">TLS / SSL</span>
        </div>
        <div class="p-3 bg-surface-sunken rounded border border-border">
            <span class="text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider block">Mail Storage</span>
            <span class="text-xs font-medium text-ink mt-0.5 block">Mail Server (Source)</span>
        </div>
    </div>

    {{-- Save Button --}}
    <div class="pt-3 flex items-center justify-end">
        <button
            wire:click="save"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
        >
            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Save Profile</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>
</div>