<div x-data="appearancePreview({ initialTheme: '{{ $theme }}', initialDensity: '{{ $density }}' })">
    <div class="space-y-6">
        {{-- Theme --}}
        <section>
            <fieldset>
                <legend class="text-xs font-semibold text-ink uppercase tracking-wider mb-2.5">Theme</legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-2.5">
                    @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="theme"
                                   value="{{ $value }}"
                                   name="theme"
                                   class="sr-only peer"
                                   @change="applyTheme('{{ $value }}')"
                                   aria-describedby="theme-{{ $value }}-desc">
                            <div class="p-3 border rounded transition-colors duration-100"
                                 :class="currentTheme === '{{ $value }}'
                                     ? 'border-primary bg-primary-subtle'
                                     : 'border-border hover:border-border-strong bg-surface-raised hover:bg-hover'">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <div class="w-2.5 h-2.5 rounded-full border"
                                         :class="{
                                             'bg-surface-raised border-border-strong': '{{ $value }}' === 'light',
                                             'bg-ink border-ink': '{{ $value }}' === 'dark',
                                             'bg-gradient-to-br from-surface-raised to-ink border-border-strong': '{{ $value }}' === 'system'
                                         }"></div>
                                    <div class="font-medium text-xs text-ink">{{ $label }}</div>
                                </div>
                                <div id="theme-{{ $value }}-desc" class="text-[11px] text-ink-tertiary">
                                    @if($value === 'system') Matches OS @else {{ ucfirst($value) }} mode @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        {{-- Density --}}
        <section>
            <fieldset>
                <legend class="text-xs font-semibold text-ink uppercase tracking-wider mb-2.5">Density</legend>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-2.5">
                    @foreach(['compact' => 'Compact', 'regular' => 'Regular', 'comfortable' => 'Comfortable'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="density"
                                   value="{{ $value }}"
                                   name="density"
                                   class="sr-only peer"
                                   @change="applyDensity('{{ $value }}')"
                                   aria-describedby="density-{{ $value }}-desc">
                            <div class="p-3 border rounded transition-colors duration-100"
                                 :class="currentDensity === '{{ $value }}'
                                     ? 'border-primary bg-primary-subtle'
                                     : 'border-border hover:border-border-strong bg-surface-raised hover:bg-hover'">
                                <div class="font-medium text-xs text-ink">{{ $label }}</div>
                                <div id="density-{{ $value }}-desc" class="text-[11px] text-ink-tertiary mt-0.5">
                                    @if($value === 'compact') Compact rows
                                    @elseif($value === 'regular') Standard rows
                                    @elseif($value === 'comfortable') Relaxed rows
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        {{-- Live Preview --}}
        <section class="pt-2 border-t border-border">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Preview</h3>
                <span class="text-xs text-ink-tertiary capitalize" x-text="densityLabel"></span>
            </div>
            <div class="bg-surface border border-border rounded overflow-hidden"
                 :class="previewDensityClass"
                 aria-label="Density preview">
                <div class="divide-y divide-border-subtle">
                    {{-- Preview Row 1 (Unread) --}}
                    <div class="message-row flex items-center bg-surface-raised">
                        <div class="flex items-center gap-1 shrink-0">
                            <input type="checkbox" class="mail-checkbox pointer-events-none" checked tabindex="-1">
                            <svg class="message-row-icon text-amber-500 fill-amber-500 shrink-0" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </div>
                        <div class="message-row-avatar rounded bg-surface-sunken border border-border text-ink-secondary flex items-center justify-center font-semibold shrink-0">
                            S
                        </div>
                        <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-0.5 sm:gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="message-row-title font-semibold text-ink truncate">Sarah Jenkins</span>
                                <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                                <span class="message-row-title text-ink font-medium truncate">Quarterly Budget Review Q3</span>
                                <span class="hidden md:inline message-row-desc text-ink-tertiary truncate">— Revised financial projections...</span>
                            </div>
                            <span class="message-row-desc text-ink-tertiary whitespace-nowrap shrink-0 tabular-nums">10:42 AM</span>
                        </div>
                    </div>

                    {{-- Preview Row 2 (Read) --}}
                    <div class="message-row flex items-center hover:bg-hover">
                        <div class="flex items-center gap-1 shrink-0">
                            <input type="checkbox" class="mail-checkbox pointer-events-none" tabindex="-1">
                            <svg class="message-row-icon text-ink-tertiary/40 fill-none stroke-currentColor shrink-0" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                            </svg>
                        </div>
                        <div class="message-row-avatar rounded bg-surface-sunken border border-border text-ink-secondary flex items-center justify-center font-semibold shrink-0">
                            G
                        </div>
                        <div class="min-w-0 flex-1 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-0.5 sm:gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="message-row-title text-ink-secondary truncate">GitHub</span>
                                <span class="message-row-title text-ink-secondary truncate">[openmail] Pull Request #42 merged</span>
                                <span class="hidden md:inline message-row-desc text-ink-tertiary truncate">— Merge branch 'main'</span>
                            </div>
                            <span class="message-row-desc text-ink-tertiary whitespace-nowrap shrink-0 tabular-nums">Yesterday</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
