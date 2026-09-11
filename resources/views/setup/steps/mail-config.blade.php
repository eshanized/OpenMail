<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Mail Server Configuration</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Connect OpenMail to your organization's IMAP (incoming) and SMTP (outgoing) mail servers.
        </p>
    </div>

    {{-- Auto-detection Panel --}}
    <div class="mb-8 p-4 sm:p-5 setup-panel border-border bg-surface-raised">
        <label for="email_domain" class="block text-sm font-semibold text-ink">
            Auto-Detect Server Settings
            <span class="font-normal text-xs text-ink-tertiary ml-1">(optional)</span>
        </label>
        <p class="text-xs text-ink-secondary mt-0.5">
            Enter an email address to look up known IMAP/SMTP configurations (e.g. Gmail, Outlook, Yahoo, Proton, cPanel).
        </p>

        <div class="mt-3 flex items-center gap-3 max-w-md">
            <div class="relative flex-1">
                <input type="email"
                       id="email_domain"
                       wire:model.blur="emailDomain"
                       class="setup-input font-medium pr-8"
                       placeholder="user@company.com"
                       autocomplete="email">
                <div wire:loading wire:target="emailDomain" class="absolute right-3 top-1/2 -translate-y-1/2">
                    <svg class="w-4 h-4 text-primary animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </div>
        </div>

        @if ($detectedProvider)
            <div class="mt-3 flex items-center gap-2 text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Detected provider: <strong class="font-semibold text-ink">{{ $detectedProvider }}</strong> &mdash; settings auto-populated below!
            </div>
        @endif
    </div>

    {{-- IMAP & SMTP Grid --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- IMAP (Incoming) --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 pb-4 border-b border-border/70 mb-5">
                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-ink">IMAP &mdash; Incoming Mail</h3>
                        <p class="text-[11px] text-ink-tertiary">Retrieves folders and email messages</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="imap_host" class="block text-xs font-semibold text-ink">Server Host <span class="text-rose-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="imap_host"
                                   wire:model="imapHost"
                                   class="setup-input font-mono text-xs"
                                   placeholder="imap.example.com"
                                   autocomplete="off">
                        </div>
                        @error('imapHost') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="imap_port" class="block text-xs font-semibold text-ink">Port <span class="text-rose-500">*</span></label>
                            <div class="mt-1">
                                <input type="number"
                                       id="imap_port"
                                       wire:model="imapPort"
                                       class="setup-input font-mono text-xs"
                                       min="1" max="65535">
                            </div>
                        </div>
                        <div>
                            <label for="imap_encryption" class="block text-xs font-semibold text-ink">Encryption <span class="text-rose-500">*</span></label>
                            <div class="mt-1">
                                <select id="imap_encryption"
                                        wire:model="imapEncryption"
                                        class="setup-input text-xs font-medium">
                                    <option value="ssl">SSL / TLS (993)</option>
                                    <option value="tls">STARTTLS (143)</option>
                                    <option value="none">None (Insecure)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="imap_username" class="block text-xs font-semibold text-ink">Username <span class="text-rose-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="imap_username"
                                   wire:model="imapUsername"
                                   class="setup-input font-mono text-xs"
                                   placeholder="user@example.com"
                                   autocomplete="username">
                        </div>
                        @error('imapUsername') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="imap_password" class="block text-xs font-semibold text-ink">Password</label>
                        <div class="mt-1">
                            <input type="password"
                                   id="imap_password"
                                   wire:model="imapPassword"
                                   class="setup-input font-mono text-xs"
                                   autocomplete="current-password"
                                   placeholder="••••••••">
                        </div>
                        @error('imapPassword') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- IMAP Test Connection --}}
            <div class="mt-6 pt-4 border-t border-border/70">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <button type="button"
                            wire:click="testImapConnection"
                            wire:loading.attr="disabled"
                            wire:target="testImapConnection"
                            class="setup-btn-secondary !py-1.5 !px-3 text-xs font-semibold">
                        <span wire:loading.remove wire:target="testImapConnection">Test IMAP</span>
                        <span wire:loading wire:target="testImapConnection" class="inline-flex items-center gap-1.5">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Connecting…
                        </span>
                    </button>

                    @if ($imapTestResult)
                        @if ($imapTestResult['success'])
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Connected
                                @if (!empty($imapTestResult['folders']))
                                    ({{ count($imapTestResult['folders']) }} folders)
                                @endif
                            </span>
                        @else
                            <span class="text-xs font-semibold text-rose-500">✗ Failed</span>
                        @endif
                    @endif
                </div>

                @if ($imapTestResult && !$imapTestResult['success'])
                    <div class="mt-3 p-3 setup-callout-error">
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $imapTestResult['error'] }}</p>
                        @if (isset($imapTestResult['technical']))
                            <div x-data="{ open: false }" class="mt-2">
                                <button type="button" @click="open = !open" class="text-[11px] text-rose-500 dark:text-rose-400 hover:underline">Technical details</button>
                                <pre x-show="open" x-transition class="mt-1.5 p-2 bg-black/40 rounded text-[11px] font-mono text-rose-300 overflow-auto max-h-24 border border-rose-500/20">{{ json_encode($imapTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- SMTP (Outgoing) --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 pb-4 border-b border-border/70 mb-5">
                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-ink">SMTP &mdash; Outgoing Mail</h3>
                        <p class="text-[11px] text-ink-tertiary">Dispatches outgoing email messages</p>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="smtp_host" class="block text-xs font-semibold text-ink">Server Host <span class="text-rose-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="smtp_host"
                                   wire:model="smtpHost"
                                   class="setup-input font-mono text-xs"
                                   placeholder="smtp.example.com"
                                   autocomplete="off">
                        </div>
                        @error('smtpHost') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="smtp_port" class="block text-xs font-semibold text-ink">Port <span class="text-rose-500">*</span></label>
                            <div class="mt-1">
                                <input type="number"
                                       id="smtp_port"
                                       wire:model="smtpPort"
                                       class="setup-input font-mono text-xs"
                                       min="1" max="65535">
                            </div>
                        </div>
                        <div>
                            <label for="smtp_encryption" class="block text-xs font-semibold text-ink">Encryption <span class="text-rose-500">*</span></label>
                            <div class="mt-1">
                                <select id="smtp_encryption"
                                        wire:model="smtpEncryption"
                                        class="setup-input text-xs font-medium">
                                    <option value="ssl">SSL / TLS (465)</option>
                                    <option value="tls">STARTTLS (587)</option>
                                    <option value="none">None (Insecure)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="smtp_username" class="block text-xs font-semibold text-ink">Username <span class="text-rose-500">*</span></label>
                        <div class="mt-1">
                            <input type="text"
                                   id="smtp_username"
                                   wire:model="smtpUsername"
                                   class="setup-input font-mono text-xs"
                                   placeholder="user@example.com"
                                   autocomplete="username">
                        </div>
                        @error('smtpUsername') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="smtp_password" class="block text-xs font-semibold text-ink">Password</label>
                        <div class="mt-1">
                            <input type="password"
                                   id="smtp_password"
                                   wire:model="smtpPassword"
                                   class="setup-input font-mono text-xs"
                                   autocomplete="current-password"
                                   placeholder="••••••••">
                        </div>
                        @error('smtpPassword') <p class="mt-1 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- SMTP Test Connection --}}
            <div class="mt-6 pt-4 border-t border-border/70">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <button type="button"
                            wire:click="testSmtpConnection"
                            wire:loading.attr="disabled"
                            wire:target="testSmtpConnection"
                            class="setup-btn-secondary !py-1.5 !px-3 text-xs font-semibold">
                        <span wire:loading.remove wire:target="testSmtpConnection">Test SMTP</span>
                        <span wire:loading wire:target="testSmtpConnection" class="inline-flex items-center gap-1.5">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Connecting…
                        </span>
                    </button>

                    @if ($smtpTestResult)
                        @if ($smtpTestResult['success'])
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Connected
                            </span>
                        @else
                            <span class="text-xs font-semibold text-rose-500">✗ Failed</span>
                        @endif
                    @endif
                </div>

                @if ($smtpTestResult && !$smtpTestResult['success'])
                    <div class="mt-3 p-3 setup-callout-error">
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $smtpTestResult['error'] }}</p>
                        @if (isset($smtpTestResult['technical']))
                            <div x-data="{ open: false }" class="mt-2">
                                <button type="button" @click="open = !open" class="text-[11px] text-rose-500 dark:text-rose-400 hover:underline">Technical details</button>
                                <pre x-show="open" x-transition class="mt-1.5 p-2 bg-black/40 rounded text-[11px] font-mono text-rose-300 overflow-auto max-h-24 border border-rose-500/20">{{ json_encode($smtpTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-10 pt-6 border-t border-border/70 flex justify-between items-center">
        <button type="button"
                wire:click="previousStep"
                class="setup-btn-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>

        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
