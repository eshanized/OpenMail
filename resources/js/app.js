import './bootstrap';
import DOMPurify from 'dompurify';

window.DOMPurify = DOMPurify;

// Theme/Density initializer — runs on DOMContentLoaded for unauthenticated pages
// Authenticated pages use the inline initializer in app.blade.php for zero-flash
document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const theme = localStorage.getItem('theme') || 'system';
    const density = localStorage.getItem('density') || 'regular';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

    // Apply theme (skip if already applied by inline initializer)
    if (!html.classList.contains('dark') && !html.classList.contains('density-regular')) {
        if (theme === 'dark' || (theme === 'system' && prefersDark)) {
            html.classList.add('dark');
        }

        // Apply density
        html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
        html.classList.add(`density-${density}`);
    }

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
});
