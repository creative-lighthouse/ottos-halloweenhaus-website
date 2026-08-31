import { createApp } from 'vue';
import EventsApp from './events/EventsApp.vue';

const mountEl = document.querySelector('.events-navigator-app');

if (mountEl) {
    const dataEl = document.getElementById('events-initial-data');
    const initial = dataEl ? JSON.parse(dataEl.textContent) : {};
    const preselectEventId = mountEl.dataset.preselectEvent
        ? Number(mountEl.dataset.preselectEvent)
        : null;

    createApp(EventsApp, {
        initial,
        preselectEventId,
        capacityUrl: mountEl.dataset.capacityUrl || null,
    }).mount(mountEl);
}
