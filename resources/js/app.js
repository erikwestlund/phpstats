import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import embed from 'vega-embed';

// <div x-data="vegaChart(spec)"> renders a Vega-Lite spec built in PHP.
Alpine.data('vegaChart', (spec) => ({
    view: null,

    async init() {
        const dark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const result = await embed(this.$el, spec, {
            actions: false,
            theme: dark ? 'dark' : undefined,
            config: { background: 'transparent' },
        });
        this.view = result.view;
    },

    destroy() {
        this.view?.finalize();
    },
}));

Livewire.start();
