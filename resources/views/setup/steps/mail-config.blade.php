<div>
    <h3 class="text-lg font-medium text-gray-900">Mail Configuration (IMAP/SMTP)</h3>
    <p class="mt-2 text-sm text-gray-600">Configure your mail server settings.</p>

    <div class="mt-4">
        <label for="email_domain" class="block text-sm font-medium text-gray-700">Your Email Address <span class="text-gray-500">(for auto-detection)</span></label>
        <input type="email" id="email_domain" name="email_domain" wire:model="emailDomain" wire:model.blur class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" placeholder="admin@example.com">
        @error('emailDomain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

        @if ($detectedProvider)
            <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-md">
                <p class="text-sm text-green-800">Detected provider: <strong>{{ $detectedProvider }}</strong> — settings pre-filled below.</p>
            </div>
        @endif
    </div>

    <div class="mt-6 grid gap-6 md:grid-cols-2">
        <!-- IMAP Section -->
        <div class="bg-gray-50 p-4 rounded-lg border">
            <h4 class="text-sm font-medium text-gray-900 mb-3">IMAP (Incoming Mail)</h4>

            <div class="space-y-3">
                <div>
                    <label for="imap_host" class="block text-sm font-medium text-gray-700">Host</label>
                    <input type="text" id="imap_host" name="imap_host" wire:model="imapHost" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('imapHost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="imap_port" class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" id="imap_port" name="imap_port" wire:model="imapPort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="993">
                    </div>

                    <div>
                        <label for="imap_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                        <select id="imap_encryption" name="imap_encryption" wire:model="imapEncryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                            <option value="ssl">SSL/TLS</option>
                            <option value="tls">STARTTLS</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="imap_username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="imap_username" name="imap_username" wire:model="imapUsername" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('imapUsername') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="imap_password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="imap_password" name="imap_password" wire:model="imapPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('imapPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <button type="button" wire:click="testImapConnection" wire:loading.attr="disabled" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span wire:loading.remove>Test IMAP Connection</span>
                        <span wire:loading>Testing...</span>
                    </button>

                    @if ($imapTestResult)
                        <span class="ml-3 text-sm" :class="$imapTestResult['success'] ? 'text-green-600' : 'text-red-600'">
                            {{ $imapTestResult['success'] ? '✓ Connected successfully! Folders: ' . implode(', ', $imapTestResult['folders']) : '✗ ' . $imapTestResult['error'] }}
                        </span>

                        @if (!$imapTestResult['success'] && isset($imapTestResult['technical']))
                            <div class="mt-2">
                                <button type="button" x-data="{ open: false }" @click="open = !open" class="text-xs text-blue-600 hover:underline">Show technical details</button>
                                <div x-show="open" x-transition class="mt-2 p-3 bg-gray-100 rounded text-xs font-mono text-gray-700 overflow-auto max-h-40">
                                    <pre>{{ json_encode($imapTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- SMTP Section -->
        <div class="bg-gray-50 p-4 rounded-lg border">
            <h4 class="text-sm font-medium text-gray-900 mb-3">SMTP (Outgoing Mail)</h4>

            <div class="space-y-3">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700">Host</label>
                    <input type="text" id="smtp_host" name="smtp_host" wire:model="smtpHost" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('smtpHost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label for="smtp_port" class="block text-sm font-medium text-gray-700">Port</label>
                        <input type="number" id="smtp_port" name="smtp_port" wire:model="smtpPort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="465">
                    </div>

                    <div>
                        <label for="smtp_encryption" class="block text-sm font-medium text-gray-700">Encryption</label>
                        <select id="smtp_encryption" name="smtp_encryption" wire:model="smtpEncryption" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                            <option value="ssl">SSL/TLS</option>
                            <option value="tls">STARTTLS</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700">Username</label>
                    <input type="text" id="smtp_username" name="smtp_username" wire:model="smtpUsername" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('smtpUsername') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="smtp_password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" id="smtp_password" name="smtp_password" wire:model="smtpPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
                    @error('smtpPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <button type="button" wire:click="testSmtpConnection" wire:loading.attr="disabled" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span wire:loading.remove>Test SMTP Connection</span>
                        <span wire:loading>Testing...</span>
                    </button>

                    @if ($smtpTestResult)
                        <span class="ml-3 text-sm" :class="$smtpTestResult['success'] ? 'text-green-600' : 'text-red-600'">
                            {{ $smtpTestResult['success'] ? '✓ Connected successfully!' : '✗ ' . $smtpTestResult['error'] }}
                        </span>

                        @if (!$smtpTestResult['success'] && isset($smtpTestResult['technical']))
                            <div class="mt-2">
                                <button type="button" x-data="{ open: false }" @click="open = !open" class="text-xs text-blue-600 hover:underline">Show technical details</button>
                                <div x-show="open" x-transition class="mt-2 p-3 bg-gray-100 rounded text-xs font-mono text-gray-700 overflow-auto max-h-40">
                                    <pre>{{ json_encode($smtpTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Previous
        </button>
        <button type="button" wire:click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Next
        </button>
    </div>
</div>