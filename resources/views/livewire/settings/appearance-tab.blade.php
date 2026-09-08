<div x-data="appearancePreview()" x-init="init()">
    <div class="space-y-6">
        {{-- Theme --}}
        <section>
            <fieldset>
                <legend class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Theme</legend>
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
                            <div class="p-4 border-2 rounded-lg transition-colors
                                {{ $theme === $value
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500' }}">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $label }}</div>
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
                <legend class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Density</legend>
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
                            <div class="p-4 border-2 rounded-lg transition-colors
                                {{ $density === $value
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                    : 'border-gray-200 hover:border-gray-300 dark:border-gray-600 dark:hover:border-gray-500' }}">
                                <div class="font-medium text-gray-900 dark:text-white">{{ $label }}</div>
                                <div id="density-{{ $value }}-desc" class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    @match($value)
                                        @case('compact') Tight spacing @break
                                        @case('regular') Balanced spacing @break
                                        @case('comfortable') Relaxed spacing @break
                                    @endmatch
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        {{-- Live Preview --}}
        <section>
            <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Live Preview</h3>
            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800 min-h-[200px]"
                 :class="previewDensityClass"
                 aria-label="Density preview">
                <div class="space-y-3">
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                        <div class="font-medium text-gray-900 dark:text-white">Sample message row</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Sender Name &lt;sender@example.com&gt;</div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                        <div class="font-medium text-gray-900 dark:text-white">Another message</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Subject line here</div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                        <div class="font-medium text-gray-900 dark:text-white">Third message</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Preview text for demonstration</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    function appearancePreview() {
        return {
            previewDensityClass: 'density-regular',
            init() {
                this.previewDensityClass = 'density-{{ $density }}';
                this.$watch('previewDensityClass', (val) => {
                    document.documentElement.classList.remove('density-compact', 'density-regular', 'density-comfortable');
                    document.documentElement.classList.add(val);
                });
            },
            applyTheme(value) {
                const html = document.documentElement;
                if (value === 'dark' || (value === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    html.classList.add('dark');
                } else {
                    html.classList.remove('dark');
                }
                // Sync localStorage for persistence across page loads
                localStorage.setItem('theme', value);
                // Dispatch browser event for cross-component sync
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: value } }));
            },
            applyDensity(value) {
                this.previewDensityClass = `density-${value}`;
                // Sync localStorage for persistence across page loads
                localStorage.setItem('density', value);
                // Dispatch browser event for cross-component sync
                window.dispatchEvent(new CustomEvent('density-changed', { detail: { density: value } }));
            }
        }
    }
</script>
