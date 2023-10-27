import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import screen from '@victoryoalli/alpinejs-screen';
window.Alpine = Alpine;

Alpine.plugin(focus);
Alpine.plugin(screen);

Alpine.start();
