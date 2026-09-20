<script setup>
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

const POLL_INTERVAL_MS = 10000;
const HIGHLIGHT_DURATION_MS = 5000;

const props = defineProps({
    statusUrl: { type: String, required: true },
    initial: { type: Object, required: true },
});

const ticket = reactive({ ...props.initial });
const scanHighlighted = ref(false);
const showCheckInCode = ref(false);
const gearImage = "./_resources/app/client/images/Zahnrad.png";
const googleWalletButtonImage = "./_resources/app/client/images/google_wallet_button_de.svg";

let pollTimer = null;
let highlightTimer = null;

async function fetchTicketData() {
    try {
        const response = await fetch(props.statusUrl);
        const data = await response.json();
        Object.assign(ticket, data);
    } catch (error) {
        console.error('Ticket status check failed', error);
    }
}

function toggleCheckInCode() {
    showCheckInCode.value = !showCheckInCode.value;
}

function onScanClick() {
    scanHighlighted.value = true;
    clearTimeout(highlightTimer);
    highlightTimer = setTimeout(() => {
        scanHighlighted.value = false;
    }, HIGHLIGHT_DURATION_MS);
}

// Body gets the coupon type as a class (VIP/Press/Staff) so the ticket-wide
// colour theming in EventTicket.scss keeps working the same as before.
watch(
    () => ticket.CouponType,
    (value, oldValue) => {
        if (oldValue) document.body.classList.remove(oldValue);
        if (value) document.body.classList.add(value);
    },
    { immediate: true },
);

onMounted(() => {
    pollTimer = setInterval(fetchTicketData, POLL_INTERVAL_MS);
});

onBeforeUnmount(() => {
    clearInterval(pollTimer);
    clearTimeout(highlightTimer);
    if (ticket.CouponType) document.body.classList.remove(ticket.CouponType);
});
</script>

<template>
    <div class="section_ticket_frame">
        <div class="section_ticket">
            <div
                class="section_scancode"
                :class="{ highlighted: scanHighlighted }"
                data-behaviour="scancode"
                @click="onScanClick"
            >
                <img :src="ticket.QRCode" alt="QR-Code">
            </div>
            <div class="section_data">
                <h2 v-if="ticket.CouponType">{{ ticket.CouponType }}</h2>
                <h1>{{ ticket.Title }}</h1>
                <div class="section_directdata">
                    <h2 v-if="ticket.GroupSize && ticket.GroupSize > 1">{{ ticket.GroupSize }} Personen</h2>
                    <h2 v-else>{{ ticket.GroupSize }} Person</h2>
                    <h2>{{ ticket.SlotTimeFormatted }} - {{ ticket.SlotTimeEndFormatted }}</h2>
                </div>
                <hr>
                <h3>{{ ticket.EventTitle }} | {{ ticket.EventDateFormatted }}</h3>
                <h4>Ort: {{ ticket.EventPlace }}</h4>
            </div>
            <Transition name="status-fade">
                <div
                    v-if="ticket.Status === 'CheckedIn' || ticket.Status === 'Cancelled'"
                    class="section_status_wrap"
                >
                    <div class="section_status" :class="{ outofway: scanHighlighted }">
                        <h2 class="status_title">
                            {{ ticket.Status === 'Cancelled' ? 'Buchung deaktiviert' : 'Check-In erfolgreich' }}
                        </h2>
                        <h3 v-if="ticket.Status !== 'Cancelled'" class="status_subline">Vielen Dank für Deinen Besuch</h3>
                        <a v-if="ticket.FeedbackPageLink" :href="ticket.FeedbackPageLink" class="status_button">Feedback abgeben</a>
                    </div>
                </div>
            </Transition>
            <div class="section_headline">
                <div class="section_logo_row">
                <svg
                    class="header_icon"
                    width="100%"
                    viewBox="0 0 52.916666 52.916666"
                    version="1.1"
                    xmlns="http://www.w3.org/2000/svg"
                    xmlns:svg="http://www.w3.org/2000/svg"
                    @click="toggleCheckInCode"
                >
                    <g>
                        <circle class="eyes" cx="22.5px" cy="17px" r="1.5px" fill="currentColor" />
                        <circle class="eyes" cx="32px" cy="17px" r="1.5px" fill="currentColor" />
                        <path
                            fill="#ffffff"
                            d="M104.586,6.369L98.545,13.752L96.309,13.752L91.834,8.83L85.123,16.438L77.068,16.66L76.844,21.582L49.326,19.121L45.748,25.609L39.707,26.504L34.783,44.402L62.525,103.914L71.699,109.283L77.291,122.258L80.648,115.77L86.688,119.797L88.254,130.09L92.059,125.168L96.084,127.627L97.875,136.801L102.125,127.404L108.166,124.496L110.627,128.746L113.088,120.693L119.352,117.561L121.812,123.154L126.512,111.744L134.564,107.939L165.217,41.717L164.098,38.361L160.74,36.797L160.518,30.979L152.016,27.623L150.896,18.227L136.578,16.885L135.461,12.186L124.721,16.213L121.365,13.977L113.535,15.543L111.969,9.725L104.586,6.369ZM106.488,12.857L110.068,14.424L114.207,37.803L109.172,37.467L106.488,12.857ZM92.17,15.766L96.309,20.24L99.664,33.328L98.434,38.025L88.59,19.234L92.17,15.766ZM120.357,19.57L123.604,22.812L126.959,39.48L124.162,39.256L120.357,19.57ZM133.445,19.682L136.803,40.152L130.762,20.576L133.445,19.682ZM80.871,21.135L84.562,21.135L86.799,29.973L86.688,39.928L80.537,25.387L80.871,21.135ZM139.936,22.926L147.43,23.709L149.332,41.494L139.936,22.926ZM53.129,24.604L64.539,25.945L64.428,41.717L53.129,24.604ZM69.35,25.498L77.738,26.729L77.068,39.48L69.35,25.498ZM46.643,31.092L47.871,42.611L45.635,42.611L41.721,35.342L41.832,32.322L46.643,31.092ZM153.693,33.439L156.379,34.67L156.043,42.725L154.588,42.053L153.693,33.439ZM104.697,42.053L104.811,86.574L109.172,82.996L100.111,101.451L89.82,79.863L96.533,84.113L99.889,42.166L104.697,42.053ZM95.748,42.947L95.414,49.66L58.611,46.193L95.748,42.947ZM110.291,43.061L139.375,45.969L109.732,48.654L110.291,43.061ZM102.441,44.061L101.678,44.066L97.762,87.246L94.742,85.791L100.447,96.195L102.441,44.061ZM151.904,50.219L110.068,77.178L109.955,54.023L151.904,50.219ZM50.334,50.666L93.736,54.805L93.064,75.611L50.334,50.666ZM155.707,53.352L132.217,104.584L101.455,122.705L101.678,109.059L115.102,79.191L155.707,53.352ZM67.562,122.443L96.83,193.631L130.684,123.867L121.033,130.352L116.92,124.342L114.863,133.99L108.535,138.262L104.264,131.303L102.365,143.008L99.518,144.748L93.98,140.16L93.35,132.408L89.078,136.84L85.281,134.783L82.434,123.867L79.586,130.668L67.562,122.443ZM121.666,134.148L97.303,184.457L99.361,148.229L121.666,134.148Z"
                            transform="scale(0.26458333)"
                        />
                    </g>
                </svg>
                <Transition name="status-fade">
                    <span v-if="showCheckInCode && ticket.CheckInCode" class="section_checkincode">{{ ticket.CheckInCode }}</span>
                </Transition>
                </div>
                <a
                    v-if="ticket.GoogleWalletLink"
                    :href="ticket.GoogleWalletLink"
                    class="section_walletbutton"
                >
                    <img :src="googleWalletButtonImage" alt="Zu Google Wallet hinzufügen">
                </a>
            </div>
            <div class="section_gear">
                <img :src="gearImage" alt="Gear">
            </div>
            <div class="section_gear2">
                <img :src="gearImage" alt="Gear">
            </div>
        </div>
    </div>
</template>
