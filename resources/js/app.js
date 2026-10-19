import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import embed from 'vega-embed';

// phiz's <x-phiz> loader renders charts with window.vegaEmbed (config/phiz.php sets assets to 'none').
// Charts use Vega's dark theme when the page is dark, whichever way it was chosen.
const isDark = () => document.documentElement.classList.contains('dark');
window.vegaEmbed = (el, spec, options = {}) => {
    const { theme, ...rest } = options;
    return embed(el, spec, isDark() ? { ...rest, theme: 'dark' } : rest);
};

// The theme toggle: light or dark, stored, or the system setting until one is chosen.
const THEME_KEY = 'phpstats-theme';

function applyTheme(dark) {
    document.documentElement.classList.toggle('dark', dark);
    // phiz redraws a chart when its options attribute changes, so nudge each one.
    document.querySelectorAll('[data-phiz]').forEach((chart) => {
        const options = chart.getAttribute('data-phiz-options') ?? '';
        chart.setAttribute('data-phiz-options', options.endsWith(' ') ? options.trimEnd() : `${options} `);
    });
}

Alpine.data('themeToggle', () => ({
    dark: isDark(),
    toggle() {
        this.dark = !this.dark;
        try {
            localStorage.setItem(THEME_KEY, this.dark ? 'dark' : 'light');
        } catch {}
        applyTheme(this.dark);
    },
}));

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
    let stored = null;
    try {
        stored = localStorage.getItem(THEME_KEY);
    } catch {}
    if (!stored) applyTheme(event.matches);
});

// <x-code-tabs>: every tabset on a page shows the same syntax, and the choice
// is remembered between pages when storage is available.
const SYNTAX_KEY = 'phpstats-syntax';

function storedSyntax() {
    try {
        return localStorage.getItem(SYNTAX_KEY) === 'chain' ? 'chain' : 'pipe';
    } catch {
        return 'pipe';
    }
}

Alpine.data('syntaxTabs', () => ({
    syntax: storedSyntax(),
    init() {
        window.addEventListener('syntax-changed', (event) => (this.syntax = event.detail));
    },
    choose(syntax) {
        try {
            localStorage.setItem(SYNTAX_KEY, syntax);
        } catch {}
        window.dispatchEvent(new CustomEvent('syntax-changed', { detail: syntax }));
    },
}));

// "On this page" in the docs: highlight the last heading scrolled past.
Alpine.data('tocSpy', () => ({
    active: null,
    init() {
        const headings = [...this.$el.querySelectorAll('[data-toc]')]
            .map((link) => document.getElementById(link.dataset.toc))
            .filter(Boolean);
        const update = () => {
            const passed = headings.filter((heading) => heading.getBoundingClientRect().top < 120);
            this.active = (passed.at(-1) ?? headings[0])?.id ?? null;
        };
        window.addEventListener('scroll', update, { passive: true });
        update();
    },
}));

// Quarto tabsets in synced docs pages. Tabs labeled as the method chain or the
// pipe follow the same remembered syntax choice as <x-code-tabs>.
function syntaxOf(link) {
    const label = link.textContent.toLowerCase();
    return label.includes('method chain') ? 'chain' : label.includes('pipe') ? 'pipe' : null;
}

function showTab(link) {
    const tabset = link.closest('.panel-tabset');
    const target = tabset?.querySelector(link.getAttribute('href') || `#${link.getAttribute('aria-controls')}`);
    if (!tabset || !target) return;
    tabset.querySelectorAll(':scope > .nav-tabs .nav-link').forEach((other) => {
        other.classList.toggle('active', other === link);
        other.setAttribute('aria-selected', other === link ? 'true' : 'false');
    });
    target.parentElement.querySelectorAll(':scope > .tab-pane').forEach((pane) => pane.classList.toggle('active', pane === target));
}

function applySyntax(syntax) {
    document.querySelectorAll('.docs-content .panel-tabset .nav-link').forEach((link) => {
        if (syntaxOf(link) === syntax) showTab(link);
    });
}

document.addEventListener('click', (event) => {
    const link = event.target.closest('.docs-content .panel-tabset .nav-link');
    if (!link) return;
    event.preventDefault();
    const syntax = syntaxOf(link);
    if (syntax) {
        try {
            localStorage.setItem(SYNTAX_KEY, syntax);
        } catch {}
        window.dispatchEvent(new CustomEvent('syntax-changed', { detail: syntax }));
        applySyntax(syntax);
    } else {
        showTab(link);
    }
});

document.addEventListener('DOMContentLoaded', () => applySyntax(storedSyntax()));
window.addEventListener('syntax-changed', (event) => applySyntax(event.detail));

// Vignette updates can add math, so typeset again after Livewire morphs the page.
Livewire.hook('morphed', () => {
    if (window.MathJax?.typesetPromise) window.MathJax.typesetPromise();
});

Livewire.start();
