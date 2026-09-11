<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Security Defaults</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            OpenMail is built with enterprise security defaults. Review the out-of-the-box protections configured for your deployment.
        </p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        {{-- HTTPS Enforcement --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-500 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">HTTPS &amp; Transport Encryption</h3>
                </div>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Mandatory SSL/TLS enforcement prevents packet sniffing and session interception across public networks.
                </p>

                <div class="mt-3.5 flex items-center gap-2">
                    <input type="checkbox" id="https_enabled" wire:model="httpsEnabled" class="h-4 w-4 text-primary rounded border-border focus:ring-primary" checked disabled>
                    <label for="https_enabled" class="text-xs font-semibold text-ink">HTTPS enforcement enabled</label>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-border/70 flex items-center justify-between text-[11px]">
                <span class="text-ink-tertiary">Current Protocol:</span>
                <span class="font-mono font-semibold {{ request()->isSecure() ? 'text-emerald-500' : 'text-amber-500' }}">
                    {{ request()->isSecure() ? 'HTTPS (Secure)' : 'HTTP (Local/Development)' }}
                </span>
            </div>
        </div>

        {{-- Cookie Protection --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Hardened Cookies</h3>
                </div>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Zero-leakage session cookie headers prevent cross-site scripting (XSS) and cross-site request forgery (CSRF).
                </p>

                <div class="mt-3 space-y-1.5 text-xs text-ink">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>HttpOnly:</strong> Blocks JavaScript document.cookie access</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        <span><strong>SameSite=Lax:</strong> Restricts cross-site transmission</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-border/70 flex items-center justify-between text-[11px]">
                <span class="text-ink-tertiary">Secure Flag:</span>
                <span class="font-mono font-semibold {{ request()->isSecure() ? 'text-emerald-500' : 'text-ink-tertiary' }}">
                    {{ request()->isSecure() ? 'Enabled' : 'Auto on HTTPS' }}
                </span>
            </div>
        </div>

        {{-- Session Lifetime --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Session Lifetime</h3>
                </div>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Sessions expire after 24 hours of inactivity to prevent unauthorized access on shared workstations.
                </p>

                <div class="mt-3">
                    <div class="flex items-center gap-2">
                        <input type="number" id="session_lifetime" value="1440" class="setup-input !w-28 font-mono text-xs !py-1.5" disabled>
                        <span class="text-xs text-ink-secondary">minutes (24 hours)</span>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-border/70 text-[11px] text-ink-tertiary">
                Configured via application environment settings
            </div>
        </div>

        {{-- Rate Limiting / Progressive Delays --}}
        <div class="setup-panel flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <div class="w-8 h-8 rounded bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-ink">Brute-Force Throttling</h3>
                </div>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Progressive delay exponential backoff against automated password spraying attacks.
                </p>

                <div class="mt-3 border border-border/80 rounded-lg overflow-hidden">
                    <table class="w-full text-[11px] text-left">
                        <thead class="bg-surface-sunken dark:bg-white/[0.04] text-ink-tertiary">
                            <tr>
                                <th class="px-2.5 py-1.5 font-semibold">Failed Attempts</th>
                                <th class="px-2.5 py-1.5 font-semibold text-right">Penalty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr>
                                <td class="px-2.5 py-1 font-mono text-ink-secondary">1–2 attempts</td>
                                <td class="px-2.5 py-1 text-right text-ink-tertiary">No delay</td>
                            </tr>
                            <tr class="bg-surface-sunken/50 dark:bg-white/[0.02]">
                                <td class="px-2.5 py-1 font-mono text-ink-secondary">3–4 attempts</td>
                                <td class="px-2.5 py-1 text-right font-medium text-amber-500">5 seconds</td>
                            </tr>
                            <tr>
                                <td class="px-2.5 py-1 font-mono text-ink-secondary">5–6 attempts</td>
                                <td class="px-2.5 py-1 text-right font-medium text-amber-500">30 seconds</td>
                            </tr>
                            <tr class="bg-surface-sunken/50 dark:bg-white/[0.02]">
                                <td class="px-2.5 py-1 font-mono text-ink-secondary">7–9 attempts</td>
                                <td class="px-2.5 py-1 text-right font-medium text-rose-500">5 minutes</td>
                            </tr>
                            <tr>
                                <td class="px-2.5 py-1 font-mono text-ink-secondary">10+ attempts</td>
                                <td class="px-2.5 py-1 text-right font-bold text-rose-500">15 min lockout</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-border/70 text-[11px] text-ink-tertiary">
                IP and account-level rate limiting enabled
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
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
