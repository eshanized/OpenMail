<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-ink">Mail Server Configuration</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Configure your company's IMAP (incoming) and SMTP (outgoing) mail server settings.
            These connect OpenMail to your existing mail infrastructure.
        </p>
    </div>

    {{-- Auto-detection --}}
    <div class="mb-6">
        <label for="email_domain" class="block text-sm font-medium text-ink-secondary">
            Your email address
            <span class="font-normal text-ink-tertiary">(optional — for auto-detecting server settings)</span>
        </label>
        <input type="email"
               id="email_domain"
               wire:model.blur="emailDomain"
               class="mt-1 block w-full max-w-sm rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
               placeholder="admin@example.com"
               autocomplete="email">
        <p class="mt-1 text-xs text-ink-tertiary">Enter your full email address to auto-fill IMAP/SMTP settings for known providers.</p>

        @if ($detectedProvider)
            <div class="mt-2 flex items-center gap-2 text-sm text-success">
                <svg class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Detected: <strong>{{ $detectedProvider }}</strong> — settings pre-filled below.
            </div>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- IMAP --}}
        <div class="bg-surface-sunken rounded-lg border border-border p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">
                <svg class="inline w-4 h-4 mr-1 text-ink-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
                IMAP — Incoming Mail
            </h3>

            <div class="space-y-4">
                <div>
                    <label for="imap_host" class="block text-sm font-medium text-ink-secondary">Host</label>
                    <input type="text"
                           id="imap_host"
                           wire:model="imapHost"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           placeholder="imap.example.com"
                           autocomplete="off">
                    @error('imapHost') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="imap_port" class="block text-sm font-medium text-ink-secondary">Port</label>
                        <input type="number"
                               id="imap_port"
                               wire:model="imapPort"
                               class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                               min="1" max="65535">
                    </div>
                    <div>
                        <label for="imap_encryption" class="block text-sm font-medium text-ink-secondary">Encryption</label>
                        <select id="imap_encryption"
                                wire:model="imapEncryption"
                                class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                            <option value="ssl">SSL/TLS (993)</option>
                            <option value="tls">STARTTLS (143)</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="imap_username" class="block text-sm font-medium text-ink-secondary">Username</label>
                    <input type="text"
                           id="imap_username"
                           wire:model="imapUsername"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           autocomplete="username">
                    @error('imapUsername') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="imap_password" class="block text-sm font-medium text-ink-secondary">Password</label>
                    <input type="password"
                           id="imap_password"
                           wire:model="imapPassword"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           autocomplete="current-password">
                    @error('imapPassword') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                {{-- IMAP test button --}}
                <div>
                    <button type="button"
                            wire:click="testImapConnection"
                            wire:loading.attr="disabled"
                            wire:target="testImapConnection"
                            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="testImapConnection">Test IMAP Connection</span>
                        <span wire:loading wire:target="testImapConnection">Testing…</span>
                    </button>

                    @if ($imapTestResult)
                        <div class="mt-2">
                            @if ($imapTestResult['success'])
                                <p class="text-sm text-success font-medium">
                                    ✓ Connected
                                    @if (!empty($imapTestResult['folders']))
                                        — {{ count($imapTestResult['folders']) }} folders found
                                    @endif
                                </p>
                            @else
                                <p class="text-sm text-danger">✗ {{ $imapTestResult['error'] }}</p>
                                @if (isset($imapTestResult['technical']))
                                    <div x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="text-xs text-primary hover:underline mt-1">Technical details</button>
                                        <pre x-show="open" x-transition class="mt-1 p-2 bg-surface-sunken rounded text-xs font-mono text-ink-secondary overflow-auto max-h-24">{{ json_encode($imapTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SMTP --}}
        <div class="bg-surface-sunken rounded-lg border border-border p-5">
            <h3 class="text-sm font-semibold text-ink mb-4">
                <svg class="inline w-4 h-4 mr-1 text-ink-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                SMTP — Outgoing Mail
            </h3>

            <div class="space-y-4">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-ink-secondary">Host</label>
                    <input type="text"
                           id="smtp_host"
                           wire:model="smtpHost"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           placeholder="smtp.example.com"
                           autocomplete="off">
                    @error('smtpHost') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-ink-secondary">Port</label>
                        <input type="number"
                               id="smtp_port"
                               wire:model="smtpPort"
                               class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                               min="1" max="65535">
                    </div>
                    <div>
                        <label for="smtp_encryption" class="block text-sm font-medium text-ink-secondary">Encryption</label>
                        <select id="smtp_encryption"
                                wire:model="smtpEncryption"
                                class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border">
                            <option value="ssl">SSL/TLS (465)</option>
                            <option value="tls">STARTTLS (587)</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-ink-secondary">Username</label>
                    <input type="text"
                           id="smtp_username"
                           wire:model="smtpUsername"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           autocomplete="username">
                    @error('smtpUsername') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-ink-secondary">Password</label>
                    <input type="password"
                           id="smtp_password"
                           wire:model="smtpPassword"
                           class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                           autocomplete="current-password">
                    @error('smtpPassword') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
                </div>

                {{-- SMTP test button --}}
                <div>
                    <button type="button"
                            wire:click="testSmtpConnection"
                            wire:loading.attr="disabled"
                            wire:target="testSmtpConnection"
                            class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                        <span wire:loading.remove wire:target="testSmtpConnection">Test SMTP Connection</span>
                        <span wire:loading wire:target="testSmtpConnection">Testing…</span>
                    </button>

                    @if ($smtpTestResult)
                        <div class="mt-2">
                            @if ($smtpTestResult['success'])
                                <p class="text-sm text-success font-medium">✓ Connected successfully</p>
                                <p class="text-xs text-ink-tertiary mt-0.5">No email was sent. Connection test only.</p>
                            @else
                                <p class="text-sm text-danger">✗ {{ $smtpTestResult['error'] }}</p>
                                @if (isset($smtpTestResult['technical']))
                                    <div x-data="{ open: false }">
                                        <button type="button" @click="open = !open" class="text-xs text-primary hover:underline mt-1">Technical details</button>
                                        <pre x-show="open" x-transition class="mt-1 p-2 bg-surface-sunken rounded text-xs font-mono text-ink-secondary overflow-auto max-h-24">{{ json_encode($smtpTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
            Continue →
        </button>
    </div>
</div>
