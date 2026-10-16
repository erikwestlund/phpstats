import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';
import embed from 'vega-embed';

// phit's <x-phit> loader renders charts with window.vegaEmbed (config/phit.php sets assets to 'none').
window.vegaEmbed = embed;

Livewire.start();
