import '@fontsource-variable/plus-jakarta-sans';
import './bootstrap';
import DOMPurify from 'dompurify';
import { initTiptapEditor, createToolbar } from './components/TiptapEditor.js';

window.DOMPurify = DOMPurify;
window.initTiptapEditor = initTiptapEditor;
window.createTiptapToolbar = createToolbar;

// Theme/Density helper functions
export function applyThemeToDoc(theme) {
    const html = document.documentElement;
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (theme === 'dark' || (theme === 'system' && prefersDark)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
}

export function applyDensityToDoc(density) {
    const html = document.documentElement;
    const valid = ['compact', 'regular', 'comfortable'].includes(density) ? density : 'regular';
    html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
    html.classList.add(`density-${valid}`);
    localStorage.setItem('density', valid);
}

function extractEventValue(e, key) {
    if (e.detail) {
        if (typeof e.detail === 'string') return e.detail;
        if (e.detail[key]) return e.detail[key];
        if (Array.isArray(e.detail) && e.detail[0] && e.detail[0][key]) return e.detail[0][key];
    }
    return null;
}

// Theme/Density initializer — runs on DOMContentLoaded for unauthenticated pages
// Authenticated pages use the inline initializer in app.blade.php for zero-flash
document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;

    // Check if inline initializer already set them
    const hasInlineTheme = html.dataset.themeInitialized === 'true' || html.classList.contains('dark');
    const hasInlineDensity = html.classList.contains('density-regular')
        || html.classList.contains('density-compact')
        || html.classList.contains('density-comfortable');

    if (!hasInlineTheme) {
        const theme = localStorage.getItem('theme') || 'system';
        applyThemeToDoc(theme);
    }
    if (!hasInlineDensity) {
        const density = localStorage.getItem('density') || 'regular';
        applyDensityToDoc(density);
    }

    // Always listen for system preference changes
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

    // Always listen for theme/density change events from Settings page or Livewire
    const handleThemeChange = (e) => {
        const newTheme = extractEventValue(e, 'theme') || 'system';
        applyThemeToDoc(newTheme);
    };

    const handleDensityChange = (e) => {
        const newDensity = extractEventValue(e, 'density') || 'regular';
        applyDensityToDoc(newDensity);
    };

    window.addEventListener('theme-changed', handleThemeChange);
    window.addEventListener('browser-theme-changed', handleThemeChange);
    window.addEventListener('density-changed', handleDensityChange);
    window.addEventListener('browser-density-changed', handleDensityChange);
});

// Route overlay fade cross (D-12) + page fade replay (D-09)
const overlay = () => document.getElementById('route-overlay');
const main = () => document.getElementById('app-main');

document.addEventListener('livewire:navigating', () => overlay()?.classList.add('opacity-100'));
document.addEventListener('livewire:navigated', () => {
    overlay()?.classList.remove('opacity-100');
    // Ensure density and theme persist across Livewire navigation
    const currentDensity = localStorage.getItem('density');
    if (currentDensity) {
        applyDensityToDoc(currentDensity);
    }
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme) {
        applyThemeToDoc(currentTheme);
    }
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
        currentTheme: props.initialTheme || 'system',
        currentDensity: props.initialDensity || 'regular',
        get previewDensityClass() {
            return `density-${this.currentDensity}`;
        },
        get densityLabel() {
            return `${this.currentDensity} spacing`;
        },
        init() {
            this.currentDensity = props.initialDensity || localStorage.getItem('density') || 'regular';
            this.currentTheme = props.initialTheme || localStorage.getItem('theme') || 'system';
            applyDensityToDoc(this.currentDensity);
        },
        applyTheme(value) {
            this.currentTheme = value;
            applyThemeToDoc(value);
            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: value } }));
        },
        applyDensity(value) {
            this.currentDensity = value;
            applyDensityToDoc(value);
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