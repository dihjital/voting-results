import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
window.Alpine = Alpine;

Alpine.plugin(focus);

Alpine.start();
