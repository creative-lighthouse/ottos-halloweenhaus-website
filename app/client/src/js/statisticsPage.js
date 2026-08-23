import { createApp } from 'vue';
import StatisticsApp from './statistics/StatisticsApp.vue';

const mountEl = document.querySelector('.statistics-page');
if (mountEl) {
    createApp(StatisticsApp).mount(mountEl);
}
