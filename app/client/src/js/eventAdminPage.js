import { createApp } from 'vue';
import EventAdminApp from './eventadmin/EventAdminApp.vue';

const mountEl = document.querySelector('.eventadmin-page');
if (mountEl) {
    createApp(EventAdminApp).mount(mountEl);
}
