import '@fontsource-variable/plus-jakarta-sans';
import './bootstrap';
import DOMPurify from 'dompurify';

window.DOMPurify = DOMPurify;

// Theme/Density initializer — runs on DOMContentLoaded for unauthenticated pages
// Authenticated pages use the inline initializer in app.blade.php for zero-flash
document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Read from localStorage (fallback for unauthenticated pages)
    let theme = localStorage.getItem('theme') || 'system';
    let density = localStorage.getItem('density') || 'regular';

    // If the inline initializer already applied classes, respect those
    // (they come from server-rendered DB settings via app.blade.php)
    if (html.classList.contains('dark') || html.classList.contains('density-regular')
        || html.classList.contains('density-compact') || html.classList.contains('density-comfortable')) {
        // Sync localStorage with what the inline initializer applied
        if (html.classList.contains('dark')) {
            localStorage.setItem('theme', 'dark');
        } else if (!html.classList.contains('dark') && prefersDark) {
            // Could be 'system' — keep as-is
        }
        return; // Inline initializer already handled it
    }

    // Apply theme (unauthenticated pages only — authenticated use inline initializer)
    if (theme === 'dark' || (theme === 'system' && prefersDark)) {
        html.classList.add('dark');
    }

    // Apply density
    html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
    html.classList.add(`density-${density}`);

    // Listen for system preference changes
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        const currentTheme = localStorage.getItem('theme') || 'system';
        if (currentTheme === 'system') {
            if (e.matches) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }
    });

    // Listen for theme/density change events from Settings page
    window.addEventListener('theme-changed', (e) => {
        const newTheme = e.detail?.theme || e.detail || 'system';
        localStorage.setItem('theme', newTheme);
        if (newTheme === 'dark' || (newTheme === 'system' && prefersDark)) {
            html.classList.add('dark');
        } else {
            html.classList.remove('dark');
        }
    });

    window.addEventListener('density-changed', (e) => {
        const newDensity = e.detail?.density || e.detail || 'regular';
        localStorage.setItem('density', newDensity);
        html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
        html.classList.add(`density-${newDensity}`);
    });
});

// Route overlay fade cross (D-12) + page fade replay (D-09)
const overlay = () => document.getElementById('route-overlay');
const main = () => document.getElementById('app-main');

document.addEventListener('livewire:navigating', () => overlay()?.classList.add('opacity-100'));
document.addEventListener('livewire:navigated', () => {
    overlay()?.classList.remove('opacity-100');
    // Replay page fade-in after SPA swaps (morph preserves the main element)
    const m = main();
    if (!m) return;
    m.classList.remove('animate-fade-in');
    void m.offsetWidth; // force reflow to restart animation
    m.classList.add('animate-fade-in');
});

// Alpine component registrations (alpine:init) — registers settings components in the Vite bundle
document.addEventListener('alpine:init', () => {
    Alpine.data('appearancePreview', (props = {}) => ({
        previewDensityClass: 'density-regular',
        init() {
            this.previewDensityClass = `density-${props.initialDensity || 'regular'}`;
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
    }));

    Alpine.data('settingsTabs', (tabKeys = []) => ({
        toastMessage: '',
        toastType: 'info',
        init() {
            // Listen for toast events from Livewire
            this.$wire.on('toast', (message, type = 'info') => {
                this.toastMessage = message;
                this.toastType = type;
            });
        },
        toastMessage: '',
        toastType: 'info',
        focusNextTab() {
            const current = this.tabKeys.indexOf(this.activeTab);
            if (current < this.tabKeys.length - 1) {
                this.selectTab(this.tabKeys[current + 1]);
            }
        },
        focusPrevTab() {
            const current = this.tabKeys.indexOf(this.activeTab);
            if (current > 0) {
                this.selectTab(this.tabKeys[current - 1]);
            }
        },
        selectTab(tab) {
            if (this.tabKeys.includes(tab)) {
                this.activeTab = tab;
                this.$dispatch('active-tab-changed', { tab });
            }
        },
        activeTab: null,
        tabKeys: tabKeys,
    }));
});