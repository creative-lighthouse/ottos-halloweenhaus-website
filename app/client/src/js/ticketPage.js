import { createApp } from 'vue';
import TicketApp from './ticket/TicketApp.vue';

const mountEl = document.querySelector('.ticket-page');
if (mountEl) {
    const statusUrl = mountEl.getAttribute('data-status-url');
    const initialDataEl = document.getElementById('ticket-initial-data');
    const initial = initialDataEl ? JSON.parse(initialDataEl.textContent) : {};
    createApp(TicketApp, { statusUrl, initial }).mount(mountEl);
}
