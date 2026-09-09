<div x-data="appearancePreview({ initialTheme: '{{ $theme }}', initialDensity: '{{ $density }}' })" x-init="init()">
    <div class="space-y-8">
        {{-- Theme --}}
        <section>
            <fieldset>
                <legend class="text-sm font-semibold text-ink mb-3">Theme</legend>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="theme"
                                   value="{{ $value }}"
                                   name="theme"
                                   class="sr-only peer"
                                   @change="applyTheme('{{ $value }}')"
                                   aria-describedby="theme-{{ $value }}-desc">
                            <div class="p-4 border-2 rounded-xl transition-all duration-150
                                {{ $theme === $value
                                    ? 'border-primary bg-primary-subtle shadow-sm'
                                    : 'border-border hover:border-border-strong bg-surface hover:bg-hover' }}">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="w-3 h-3 rounded-full border-2
                                        {{ $value === 'light' ? 'bg-surface-raised border-border' : '' }}
                                        {{ $value === 'dark' ? 'bg-ink border-border-strong' : '' }}
                                        {{ $value === 'system' ? 'bg-gradient-to-br from-surface-raised to-ink border-border' : '' }}"></div>
                                    <div class="font-medium text-sm text-ink">{{ $label }}</div>
                                </div>
                                <div id="theme-{{ $value }}-desc" class="text-xs text-ink-tertiary">
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
                <legend class="text-sm font-semibold text-ink mb-3">Density</legend>
                <div class="grid grid-cols-3 gap-3">
                    @foreach(['compact' => 'Compact', 'regular' => 'Regular', 'comfortable' => 'Comfortable'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="density"
                                   value="{{ $value }}"
                                   name="density"
                                   class="sr-only peer"
                                   @change="applyDensity('{{ $value }}')"
                                   aria-describedby="density-{{ $value }}-desc">
                            <div class="p-4 border-2 rounded-xl transition-all duration-150
                                {{ $density === $value
                                    ? 'border-primary bg-primary-subtle shadow-sm'
                                    : 'border-border hover:border-border-strong bg-surface hover:bg-hover' }}">
                                <div class="font-medium text-sm text-ink">{{ $label }}</div>
                                <div id="density-{{ $value }}-desc" class="text-xs text-ink-tertiary mt-1">
                                    @if($value === 'compact') Tight spacing
                                    @elseif($value === 'regular') Balanced spacing
                                    @elseif($value === 'comfortable') Relaxed spacing
                                    @endif
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        {{-- Live Preview --}}
        <section>
            <h3 class="text-sm font-semibold text-ink mb-3">Preview</h3>
            <div class="bg-surface-sunken rounded-xl p-4 min-h-[160px] border border-border-subtle"
                 :class="previewDensityClass"
                 aria-label="Density preview">
                <div class="space-y-2">
                    <div class="p-3 bg-surface-raised rounded-lg border border-border-subtle">
                        <div class="font-medium text-sm text-ink">Sample message row</div>
                        <div class="text-xs text-ink-secondary">Sender Name &lt;sender@example.com&gt;</div>
                    </div>
                    <div class="p-3 bg-surface-raised rounded-lg border border-border-subtle">
                        <div class="font-medium text-sm text-ink">Another message</div>
                        <div class="text-xs text-ink-secondary">Subject line preview here</div>
                    </div>
                    <div class="p-3 bg-surface-raised rounded-lg border border-border-subtle">
                        <div class="font-medium text-sm text-ink">Third message</div>
                        <div class="text-xs text-ink-secondary">Preview text for demonstration</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
