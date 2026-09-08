<div x-data="appearancePreview({ initialTheme: '{{ $theme }}', initialDensity: '{{ $density }}' })" x-init="init()">
    <div class="space-y-6">
        {{-- Theme --}}
        <section>
            <fieldset>
                <legend class="text-lg font-semibold mb-4 text-ink">Theme</legend>
                <div class="grid grid-cols-3 gap-4">
                    @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="theme"
                                   value="{{ $value }}"
                                   name="theme"
                                   class="sr-only peer"
                                   @change="applyTheme('{{ $value }}')"
                                   aria-describedby="theme-{{ $value }}-desc">
                            <div class="glass-card p-4 border-2 rounded-lg transition-colors
                                {{ $theme === $value
                                    ? 'border-primary ring-2 ring-primary ring-offset-2'
                                    : 'border-white/60 dark:border-white/10 hover:border-primary/50 dark:hover:border-primary/50' }}">
                                <div class="font-medium text-ink">{{ $label }}</div>
                                <div id="theme-{{ $value }}-desc" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    @if($value === 'system') Matches OS preference @else {{ ucfirst($value) }} mode @endif
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
                <legend class="text-lg font-semibold mb-4 text-ink">Density</legend>
                <div class="grid grid-cols-3 gap-4">
                    @foreach(['compact' => 'Compact', 'regular' => 'Regular', 'comfortable' => 'Comfortable'] as $value => $label)
                        <label class="relative cursor-pointer">
                            <input type="radio"
                                   wire:model.live="density"
                                   value="{{ $value }}"
                                   name="density"
                                   class="sr-only peer"
                                   @change="applyDensity('{{ $value }}')"
                                   aria-describedby="density-{{ $value }}-desc">
                            <div class="glass-card p-4 border-2 rounded-lg transition-colors
                                {{ $density === $value
                                    ? 'border-primary ring-2 ring-primary ring-offset-2'
                                    : 'border-white/60 dark:border-white/10 hover:border-primary/50 dark:hover:border-primary/50' }}">
                                <div class="font-medium text-ink">{{ $label }}</div>
                                <div id="density-{{ $value }}-desc" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
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
            <h3 class="text-lg font-semibold mb-4 text-ink">Live Preview</h3>
            <div class="glass-card rounded-lg p-4 min-h-[200px]"
                 :class="previewDensityClass"
                 aria-label="Density preview">
                <div class="space-y-3">
                    <div class="p-3 bg-surface-raised/50 dark:bg-surface-raised/50 rounded">
                        <div class="font-medium text-ink">Sample message row</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Sender Name <sender@example.com></div>
                    </div>
                    <div class="p-3 bg-surface-raised/50 dark:bg-surface-raised/50 rounded">
                        <div class="font-medium text-ink">Another message</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Subject line here</div>
                    </div>
                    <div class="p-3 bg-surface-raised/50 dark:bg-surface-raised/50 rounded">
                        <div class="font-medium text-ink">Third message</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Preview text for demonstration</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>