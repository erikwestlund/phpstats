import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import embed from 'vega-embed';

// phiz's <x-phiz> loader renders charts with window.vegaEmbed (config/phiz.php sets assets to 'none').
// Charts use Vega's dark theme when the page is dark, whichever way it was chosen, on the page's
// own background, with the theme's white axes and #888 grid and frame toned down to the site's grays
// (zinc-600, -800, -400, and -300).
const isDark = () => document.documentElement.classList.contains('dark');
const darkAxis = {
    domainColor: '#56566d',
    tickColor: '#56566d',
    gridColor: '#2c2c3d',
    labelColor: '#9c9cb2',
    titleColor: '#cbcbd9',
};
window.vegaEmbed = (el, spec, options = {}) => {
    const { theme, ...rest } = options;
    if (!isDark()) return embed(el, spec, rest);
    const config = rest.config ?? {};
    return embed(el, spec, {
        ...rest,
        theme: 'dark',
        config: {
            background: 'transparent',
            ...config,
            axis: { ...darkAxis, ...config.axis },
            view: { stroke: darkAxis.gridColor, ...config.view },
            legend: { labelColor: '#cbcbd9', titleColor: '#cbcbd9', ...config.legend },
            title: { color: '#e3e3ec', ...config.title },
            text: { color: '#cbcbd9', ...config.text },
            header: { labelColor: '#cbcbd9', titleColor: '#cbcbd9', ...config.header },
        },
    });
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

// The landing hero's examples, shown one at a time. The carousel moves to the
// next every five seconds until a visitor uses it, and not at all for visitors
// who ask for reduced motion.
Alpine.data('heroCarousel', (count) => ({
    count,
    current: 0,
    timer: null,
    paused: false,
    init() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        this.timer = setInterval(() => {
            if (!this.paused) {
                this.current = (this.current + 1) % this.count;
            }
        }, 5000);
    },
    stop() {
        clearInterval(this.timer);
        this.timer = null;
    },
    show(index) {
        this.stop();
        this.current = (index + this.count) % this.count;
    },
}));

// "On this page" in the docs: highlight the last heading scrolled past.
// Copies text, with a textarea fallback for pages served over plain http,
// where navigator.clipboard is missing.
async function copyText(text) {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const area = Object.assign(document.createElement('textarea'), { value: text });
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.append(area);
        area.select();
        document.execCommand('copy');
        area.remove();
    }
}

// A Copy button on every code block of a synced docs page. Quarto marks the
// blocks for copying, but its own button is script the import leaves out.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.docs-content div.sourceCode').forEach((block) => {
        const code = block.querySelector('pre code');
        if (!code || block.querySelector(':scope > .docs-copy')) return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'docs-copy';
        button.textContent = 'Copy';
        button.setAttribute('aria-label', 'Copy code');
        button.addEventListener('click', async () => {
            // The import gives each line its own block, so the code is its lines joined.
            const lines = code.querySelectorAll(':scope > .code-line');
            const text = lines.length ? [...lines].map((line) => line.textContent).join('\n') : code.innerText;
            await copyText(text.replace(/\n$/, ''));
            button.textContent = 'Copied';
            clearTimeout(button.timer);
            button.timer = setTimeout(() => (button.textContent = 'Copy'), 2000);
        });
        block.append(button);
    });
});

// A benchmark workload's Results, Methods, and Reproduction tabs. The arrow
// keys, Home, and End move between them, and #{id}-methods and the like open
// a tab and scroll to its workload. Before this runs, CSS keeps only the
// Results panel showing, and without scripts all three follow one another.
Alpine.data('evidenceTabs', (id) => ({
    tab: 'results',
    order: ['results', 'methods', 'reproduction'],
    init() {
        const open = () => {
            const hash = decodeURIComponent(location.hash.slice(1));
            const name = this.order.find((t) => hash === `${id}-${t}`);
            if (name) {
                this.tab = name;
                this.$nextTick(() => this.$el.scrollIntoView());
            }
        };
        open();
        window.addEventListener('hashchange', open);
        this.$el.classList.add('tabs-ready');
    },
    select(name, focus = false) {
        this.tab = name;
        if (focus) this.$nextTick(() => this.$refs[name].focus());
    },
    key(event) {
        let i = this.order.indexOf(this.tab);
        const moves = { ArrowRight: i + 1, ArrowLeft: i - 1 + this.order.length, Home: 0, End: this.order.length - 1 };
        if (!(event.key in moves)) return;
        event.preventDefault();
        this.select(this.order[moves[event.key] % this.order.length], true);
    },
}));

// <x-command>'s Copy button: the command exactly as shown.
Alpine.data('copyCommand', (text) => ({
    copied: false,
    async copy() {
        await copyText(text);
        this.copied = true;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => (this.copied = false), 2000);
    },
}));

Alpine.data('tocSpy', () => ({
    active: null,
    init() {
        const headings = [...this.$el.querySelectorAll('[data-toc]')]
            .map((link) => document.getElementById(link.dataset.toc))
            .filter(Boolean);
        const update = () => {
            // At the bottom of the page the last sections cannot scroll up to
            // the line, so the last one counts as reached.
            const bottom = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 2;
            const passed = bottom ? headings : headings.filter((heading) => heading.getBoundingClientRect().top < 120);
            this.active = (passed.at(-1) ?? headings[0])?.id ?? null;
            // A list taller than its box, as on a long reference page,
            // scrolls itself to keep the current entry in view.
            const box = this.$el;
            const link = this.active && box.querySelector(`[data-toc="${CSS.escape(this.active)}"]`);
            if (link && box.scrollHeight > box.clientHeight + 1) {
                const top = link.getBoundingClientRect().top - box.getBoundingClientRect().top + box.scrollTop;
                if (top < box.scrollTop || top > box.scrollTop + box.clientHeight - 40) box.scrollTop = Math.max(0, top - box.clientHeight / 3);
            }
        };
        window.addEventListener('scroll', update, { passive: true });
        // A jump to a #fragment can land after init, without a scroll event.
        window.addEventListener('load', update);
        window.addEventListener('hashchange', update);
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
