{{--
  Signature Dropdown Component for Composer
  Usage: <x-signature-dropdown :signatures="$signatures" :default-signature="$defaultSignature" />

  Props:
    - signatures (Collection): User's signatures
    - defaultSignature (?Signature): Current default signature
--}}
@props([
    'signatures' => collect(),
    'defaultSignature' => null,
])

<div
    x-data="{
        open: false,
        signatures: @js($signatures->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'content_html' => $s->content_html,
            'is_default' => $s->is_default,
        ])->toArray()),
        defaultSignature: @js($defaultSignature ? [
            'id' => $defaultSignature->id,
            'name' => $defaultSignature->name,
            'content_html' => $defaultSignature->content_html,
        ] : null),
        selectedSignature: null,
        init() {
            this.selectedSignature = this.defaultSignature;
        },
        selectSignature(sig) {
            this.selectedSignature = sig;
            this.$wire.setSignature(sig.id);
            this.open = false;
        },
        removeSignature() {
            this.selectedSignature = null;
            this.$wire.removeSignature();
            this.open = false;
        }
    }"
    class="relative inline-block"
>
    {{-- Dropdown trigger --}}
    <button
        type="button"
        @click="open = !open"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 border border-gray-200 dark:border-gray-700 rounded-md hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
        </svg>
        <span x-text="selectedSignature?.name || 'No signature'"></span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Dropdown menu --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        @click.away="open = false"
        class="absolute z-50 mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg"
    >
        <div class="py-1 max-h-60 overflow-y-auto">
            @if($signatures->isEmpty())
                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                    No signatures created yet.
                </div>
            @else
                @foreach($signatures as $signature)
                    <button
                        type="button"
                        @click="selectSignature({
                            id: {{ $signature->id }},
                            name: @js($signature->name),
                            content_html: @js($signature->content_html),
                            is_default: @js($signature->is_default)
                        })"
                        class="w-full px-4 py-2 text-left text-sm hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center gap-2 transition-colors"
                        :class="selectedSignature?.id === {{ $signature->id }} ? 'bg-blue-50 dark:bg-blue-900/30' : ''"
                    >
                        <span class="flex-1 truncate">{{ $signature->name }}</span>
                        @if($signature->is_default)
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                Default
                            </span>
                        @endif
                    </button>
                @endforeach

                <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>

                {{-- No signature option --}}
                <button
                    type="button"
                    @click="removeSignature()"
                    class="w-full px-4 py-2 text-left text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    :class="!selectedSignature ? 'bg-gray-50 dark:bg-gray-700' : ''"
                >
                    No signature
                </button>
            @endif
        </div>

        {{-- Manage link --}}
        <div class="border-t border-gray-200 dark:border-gray-700">
            <a
                href="{{ route('settings') }}"
                wire:navigate
                class="block px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
                Manage signatures...
            </a>
        </div>
    </div>
</div>
