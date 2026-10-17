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

Livewire.start();
