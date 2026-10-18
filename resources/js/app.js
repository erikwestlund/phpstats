import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import embed from 'vega-embed';

// phiz's <x-phiz> loader renders charts with window.vegaEmbed (config/phiz.php sets assets to 'none').
window.vegaEmbed = embed;

// <x-code-tabs>: every tabset on a page shows the same syntax, and the choice
// is remembered between pages when storage is available.
const SYNTAX_KEY = 'phpstats-syntax';

function storedSyntax() {
    try {
        return localStorage.getItem(SYNTAX_KEY) === 'pipe' ? 'pipe' : 'chain';
    } catch {
        return 'chain';
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
