@props(['delay' => 10000, 'pendingSendId' => null])

<div
    x-data="undoSendToast({{ $delay }})"
    x-show="visible"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 transform translate-y-2"
    x-transition:leave="transition ease-in duration-200"
    class="fixed bottom-4 right-4 z-50 flex items-center gap-3 bg-surface-overlay border border-border rounded-xl shadow-lg p-4 min-w-[280px] max-w-md"
    role="alert"
    aria-live="polite"
>
    <div class="flex-1">
        <p class="text-sm font-semibold text-ink">Message sent</p>
        <div class="mt-2 h-1 bg-surface-sunken rounded-full overflow-hidden">
            <div
                class="h-full bg-primary rounded-full"
                x-ref="progress"
                style="width: 100%; transition: width 1s linear;"
            ></div>
        </div>
    </div>

    <button
        @click="undo()"
        class="px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary-subtle rounded-lg transition-colors"
        :disabled="!visible"
    >
        Undo
    </button>

    <button
        @click="dismiss()"
        class="p-1 text-ink-tertiary hover:text-ink rounded transition-colors"
        aria-label="Dismiss"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>

<script>
function undoSendToast(initialDelay = 10000) {
    return {
        visible: true,
        timer: null,
        progressEl: null,
        duration: initialDelay,

        init() {
            this.duration = initialDelay || 10000;
            this.startCountdown();
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
            this.$wire.undoSend();
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
