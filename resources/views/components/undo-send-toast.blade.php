{{-- Undo Send Toast Component --}}
{{-- Props: $delay (int, milliseconds), $pendingSendId (int) --}}

<div
    x-data="undoSendToast()"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:leave="transition ease-in duration-200"
    class="fixed bottom-4 right-4 z-50 flex items-center gap-3 bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-[300px] max-w-md"
    role="alert"
    aria-live="polite"
>

    <div class="flex-1">
        <p class="font-medium text-gray-900">Message sent</p>
        <div class="mt-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div
                class="h-full bg-blue-600"
                x-ref="progress"
                style="width: 100%; transition: width 1s linear;"
            ></div>
        </div>
    </div>

    <button
        @click="undo()"
        class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
        :disabled="!visible"
    >
        Undo
    </button>

    <button
        @click="dismiss()"
        class="text-gray-400 hover:text-gray-600"
        aria-label="Dismiss"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
</div>

<script>
function undoSendToast() {
    return {
        visible: false,
        timer: null,
        progressEl: null,
        duration: 10000, // ms from config

        init() {
            this.duration = this.$el.dataset.delay || 10000;
            this.$watch('visible', (v) => {
                if (v) this.startCountdown();
            });
        },

        show(delay = 10000) {
            this.duration = delay;
            this.visible = true;
        },

        startCountdown() {
            this.progressEl = this.$refs.progress;
            const start = Date.now();
            const animate = () => {
                const elapsed = Date.now() - start;
                const progress = Math.max(0, 1 - elapsed / this.duration);
                this.progressEl.style.width = (progress * 100) + '%';

                if (progress > 0) {
                    this.timer = requestAnimationFrame(animate);
                } else {
                    this.hide();
                }
            };
            animate();
        },

        undo() {
            cancelAnimationFrame(this.timer);
            this.visible = false;
            this.$wire.undoSend(); // Calls Livewire method
        },

        dismiss() {
            cancelAnimationFrame(this.timer);
            this.visible = false;
        },

        hide() {
            this.visible = false;
        }
    }
}
</script>