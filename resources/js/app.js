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
    const np = document.getElementById('nprogress');
    if (np) {
        np.remove();
    }
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

// Clean up progress bar if a request fails or times out
document.addEventListener('livewire:init', () => {
    if (window.Livewire?.hook) {
        window.Livewire.hook('request', ({ fail }) => {
            fail(() => {
                const np = document.getElementById('nprogress');
                if (np) np.remove();
            });
        });
    }
});

// Register the PWA shell only on secure origins. The worker deliberately does not cache
// authenticated pages, message bodies, search results, or Livewire requests.
if ('serviceWorker' in navigator && window.isSecureContext) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(() => {
            // PWA installation is optional; keep webmail functional when registration is unavailable.
        });
    }, { once: true });
}

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
            const storedDensity = localStorage.getItem('density');
            const storedTheme = localStorage.getItem('theme');

            this.currentDensity = (props.initialDensity && props.initialDensity !== 'regular')
                ? props.initialDensity
                : (storedDensity || props.initialDensity || 'regular');

            this.currentTheme = (props.initialTheme && props.initialTheme !== 'system')
                ? props.initialTheme
                : (storedTheme || props.initialTheme || 'system');

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

    Alpine.data('commandPalette', (urls = {}) => ({
        open: false,
        query: '',
        activeIndex: 0,
        actions: [],
        init() {
            this.actions = [
                { id: 'compose', label: 'Compose email', description: 'Start a new message', icon: 'compose', shortcut: 'C' },
                { id: 'inbox', label: 'Open inbox', description: 'View your incoming mail', icon: 'inbox', shortcut: 'G I' },
                { id: 'search', label: 'Search mail', description: 'Focus the mailbox search field', icon: 'search', shortcut: '/' },
                { id: 'scheduled', label: 'Scheduled messages', description: 'Review or cancel future sends', icon: 'clock' },
                { id: 'snoozed', label: 'Snoozed messages', description: 'Review messages hidden until their return time', icon: 'clock' },
                { id: 'saved-searches', label: 'Saved searches', description: 'Run or manage your reusable search filters', icon: 'search' },
                { id: 'templates', label: 'Email templates', description: 'Manage reusable message text', icon: 'template' },
                { id: 'delivery-activity', label: 'Delivery activity', description: 'Review sent, failed, and uncertain messages', icon: 'clock' },
                { id: 'follow-up-reminders', label: 'Follow-up reminders', description: 'Review due and upcoming follow-ups', icon: 'clock' },
                { id: 'settings', label: 'Settings', description: 'Appearance, mail, and security preferences', icon: 'settings' },
            ];
        },
        get filteredActions() {
            const query = this.query.trim().toLocaleLowerCase();
            if (!query) return this.actions;
            return this.actions.filter(action =>
                [action.label, action.description, action.id].some(value =>
                    value.toLocaleLowerCase().includes(query)
                )
            );
        },
        openPalette() {
            this.open = true;
            this.query = '';
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.queryInput?.focus());
        },
        closePalette() {
            this.open = false;
            this.query = '';
            this.activeIndex = 0;
        },
        handleKeydown(event) {
            const key = event.key.toLowerCase();
            if ((event.metaKey || event.ctrlKey) && key === 'k') {
                event.preventDefault();
                this.open ? this.closePalette() : this.openPalette();
                return;
            }

            if (!this.open) {
                const target = event.target;
                const isTyping = target instanceof HTMLElement
                    && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
                if (isTyping || event.metaKey || event.ctrlKey || event.altKey) return;

                if (key === 'c') {
                    event.preventDefault();
                    this.execute(this.actions.find(action => action.id === 'compose'));
                } else if (key === '/') {
                    event.preventDefault();
                    this.execute(this.actions.find(action => action.id === 'search'));
                }
                return;
            }

            if (key === 'escape') {
                event.preventDefault();
                this.closePalette();
            } else if (key === 'arrowdown') {
                event.preventDefault();
                this.activeIndex = Math.min(this.activeIndex + 1, Math.max(0, this.filteredActions.length - 1));
            } else if (key === 'arrowup') {
                event.preventDefault();
                this.activeIndex = Math.max(0, this.activeIndex - 1);
            } else if (key === 'enter') {
                event.preventDefault();
                const action = this.filteredActions[this.activeIndex];
                if (action) this.execute(action);
            }
        },
        execute(action) {
            if (!action) return;
            this.closePalette();

            if (action.id === 'compose') {
                if (document.querySelector('[data-openmail-composer]') && window.Livewire) {
                    window.Livewire.dispatch('openComposer', { mode: 'compose' });
                } else {
                    window.location.assign(urls.inboxUrl || urls.mailboxUrl);
                }
                return;
            }

            if (action.id === 'search') {
                if (document.querySelector('[data-openmail-search-input]') && window.Livewire) {
                    window.Livewire.dispatch('focus-search');
                } else {
                    window.location.assign(urls.inboxUrl || urls.mailboxUrl);
                }
                return;
            }

            const destination = {
                inbox: urls.inboxUrl || urls.mailboxUrl,
                scheduled: urls.scheduledUrl,
                snoozed: urls.snoozedUrl,
                'saved-searches': urls.savedSearchesUrl,
                'delivery-activity': urls.deliveryActivityUrl,
                'follow-up-reminders': urls.remindersUrl,
                templates: urls.templatesUrl,
                settings: urls.settingsUrl,
            }[action.id];

            if (destination) window.location.assign(destination);
        },
    }));
});