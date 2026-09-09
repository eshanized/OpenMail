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
        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-ink-secondary hover:text-ink border border-border rounded-xl bg-surface hover:bg-hover transition-all shadow-2xs cursor-pointer"
    >
        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
        </svg>
        <span class="truncate max-w-[120px]" x-text="selectedSignature?.name || 'No signature'"></span>
        <svg class="w-3 h-3 text-ink-tertiary transition-transform duration-150" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    {{-- Dropdown menu --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        @click.away="open = false"
        class="absolute z-50 bottom-full mb-2 left-0 sm:left-auto sm:right-0 sm:bottom-auto sm:top-full sm:mt-1.5 w-64 bg-surface-raised border border-border rounded-xl shadow-xl overflow-hidden backdrop-blur-md"
    >
        <div class="py-1 max-h-60 overflow-y-auto divide-y divide-border-subtle">
            @if($signatures->isEmpty())
                <div class="px-4 py-3 text-xs text-ink-tertiary text-center">
                    No signatures created yet.
                </div>
            @else
                <div class="py-1">
                    @foreach($signatures as $signature)
                        <button
                            type="button"
                            @click="selectSignature({
                                id: {{ $signature->id }},
                                name: @js($signature->name),
                                content_html: @js($signature->content_html),
                                is_default: @js($signature->is_default)
                            })"
                            class="w-full px-3 py-2 text-left text-xs hover:bg-hover flex items-center justify-between gap-2 transition-colors cursor-pointer"
                            :class="selectedSignature?.id === {{ $signature->id }} ? 'bg-primary-subtle text-primary font-semibold' : 'text-ink'"
                        >
                            <span class="truncate flex-1">{{ $signature->name }}</span>
                            @if($signature->is_default)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-primary-subtle text-primary border border-primary/20 shrink-0">
                                    Default
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- No signature option --}}
                <div class="py-1">
                    <button
                        type="button"
                        @click="removeSignature()"
                        class="w-full px-3 py-2 text-left text-xs text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                        :class="!selectedSignature ? 'bg-surface-sunken font-semibold text-ink' : ''"
                    >
                        No signature
                    </button>
                </div>
            @endif
        </div>

        {{-- Manage link --}}
        <div class="border-t border-border bg-surface-sunken/40">
            <a
                href="{{ route('settings') }}?tab=signatures"
                wire:navigate
                class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-primary hover:text-primary-hover hover:bg-hover transition-colors"
            >
                <span>Manage signatures</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </a>
        </div>
    </div>
</div>
