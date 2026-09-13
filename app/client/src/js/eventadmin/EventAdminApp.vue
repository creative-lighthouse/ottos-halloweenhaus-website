<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const iconEnter = "./_resources/app/client/icons/event_admin/icon_enter.svg";
const iconIncrease = "./_resources/app/client/icons/event_admin/icon_increase.svg";
const iconDecrease = "./_resources/app/client/icons/event_admin/icon_decrease.svg";
const iconVirtualQueue = "./_resources/app/client/icons/event_admin/icon_virtualqueue.svg";
const iconStandbyQueue = "./_resources/app/client/icons/event_admin/icon_standbyqueue.svg";
const iconQueueTotal = "./_resources/app/client/icons/event_admin/icon_queuetotal.svg";
const iconPerson = "./_resources/app/client/icons/event_admin/icon_person.svg";
const iconScary = "./_resources/app/client/icons/scary.svg";
const iconMagic = "./_resources/app/client/icons/magic.svg";
const iconEmpty = "./_resources/app/client/icons/empty.svg";

const showTypeOptions = ['Scary', 'Magic', 'Empty'];
const showTypeIndex = ref(1); // Magic (normal show) is the default, middle position

const qrVideo = ref(null);
const manualCode = ref('');
const clockText = ref('00:00');

const loading = ref(false);
const popupActive = ref(false);
const popupValid = ref(false);
const popupState = ref(''); // 'valid' | 'problematic' | 'invalid'
const message = ref('');
const name = ref('');
const groupSize = ref(0);
const eventTitle = ref('');
const timeSlotText = ref('');
const timeDifferenceText = ref('');

const amountSQ = ref(0);
const amountVQ = ref(0);
const amountTT = computed(() => amountVQ.value + amountSQ.value);
const showType = computed(() => showTypeOptions[showTypeIndex.value]);

// Hashes of the Virtual Queue registrations checked in since the last "Show
// betreten" - sent along so the resulting EntryLog records exactly who was
// let in as part of the group, not just a headcount.
const checkedInHashes = ref([]);

let currentEventId = null;
let currentCode = null;
let currentHash = null;
let qrScanner = null;
let timeDifferenceInterval = null;
let clockInterval = null;

function checkCode(code) {
    if (code === '' || popupActive.value) {
        return;
    }
    loading.value = true;

    fetch('./api/checkCode/' + code, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ hash: code }),
    })
        .then((response) => response.json())
        .then((data) => {
            loading.value = false;
            popupActive.value = true;
            message.value = data.Message;
            popupValid.value = !!data.Valid;

            if (data.Valid) {
                popupState.value = data.Status === 'Confirmed' ? 'valid' : 'problematic';
                name.value = data.Name;
                groupSize.value = data.GroupSize;
                eventTitle.value = data.Event;
                timeSlotText.value = data.TimeSlot;

                currentEventId = data.EventID;
                currentCode = code;
                currentHash = data.Hash;

                clearInterval(timeDifferenceInterval);
                timeDifferenceInterval = setInterval(() => {
                    if (popupActive.value) {
                        timeDifferenceText.value = calculateTimeDifference(data.TimeSlot);
                    }
                }, 1000);
            } else {
                popupState.value = 'invalid';
            }
        })
        .catch(() => {
            closePopup();
        });
}

function calculateTimeDifference(time) {
    const currentTime = new Date();

    const [datePart, timePart] = time.split(' ');
    const [day, month, year] = datePart.split('.');
    const [ticketHours, ticketMinutes] = timePart.split(':');
    const ticketTime = new Date(year, month - 1, day, ticketHours, ticketMinutes);

    const difference = ticketTime - currentTime;
    const differenceAbs = Math.abs(difference);
    const days = Math.floor(differenceAbs / (1000 * 60 * 60 * 24));
    const hours = Math.floor((differenceAbs % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((differenceAbs % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((differenceAbs % (1000 * 60)) / 1000);

    const sign = difference < 0 ? '-' : '';
    let result = difference < 0 ? 'Abgelaufen seit: ' : 'Gültig in: ';

    if (days !== 0) {
        result += sign + days + (days === 1 ? ' Tag, ' : ' Tage, ');
    }
    if (hours !== 0) {
        result += sign + hours + (hours === 1 ? ' Stunde, ' : ' Stunden, ');
    }
    if (minutes !== 0) {
        result += sign + minutes + (minutes === 1 ? ' Minute ' : ' Minuten ');
    }
    if (days === 0 && hours === 0 && minutes === 0) {
        result += sign + seconds + (seconds === 1 ? ' Sekunde.' : ' Sekunden.');
    } else {
        result += 'und ' + sign + seconds + (seconds === 1 ? ' Sekunde.' : ' Sekunden.');
    }

    return result;
}

function checkinGuest() {
    const eventId = currentEventId;
    const code = currentCode;
    const hash = currentHash;
    closePopup();

    fetch('./api/checkin?event=' + eventId + '&hash=' + code, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ hash: code, eventid: eventId }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.Valid) {
                amountVQ.value += data.GroupSize;
                if (hash) {
                    checkedInHashes.value.push(hash);
                }
            }
        });
}

function cancelTicket() {
    const eventId = currentEventId;
    const code = currentCode;
    closePopup();

    fetch('./api/cancelTicket?event=' + eventId + '&hash=' + code, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ hash: code, eventid: eventId }),
    });
}

function increaseSQ() {
    amountSQ.value++;
}

function decreaseSQ() {
    if (amountSQ.value > 0) {
        amountSQ.value--;
    }
}

function enterShow() {
    if (amountTT.value === 0) {
        return;
    }
    navigator.vibrate?.(1200);

    fetch('./api/enterShow', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sq: amountSQ.value,
            vq: amountVQ.value,
            tt: amountTT.value,
            type: showType.value,
            vqHashes: checkedInHashes.value,
        }),
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.Valid) {
                amountSQ.value = 0;
                amountVQ.value = 0;
                checkedInHashes.value = [];
            }
        });
}

function closePopup() {
    popupActive.value = false;
    message.value = '';
    name.value = '';
    eventTitle.value = '';
    timeSlotText.value = '';
    timeDifferenceText.value = '';
    clearInterval(timeDifferenceInterval);
}

function submitManualCode() {
    const code = manualCode.value.trim().toUpperCase();
    manualCode.value = '';
    checkCode(code);
}

function updateClock() {
    const now = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    clockText.value = pad(now.getDate()) + '.' + pad(now.getMonth() + 1) + '. | ' + pad(now.getHours()) + ':' + pad(now.getMinutes());
}

onMounted(async () => {
    updateClock();
    clockInterval = setInterval(updateClock, 1000);

    // Resolved against the page's own origin (not import.meta.url) because in
    // dev this module is served from the Vite dev server, while the static
    // file itself always lives in public/ on the actual site origin.
    const qrScannerUrl = window.location.origin + '/qr-scanner.min.js';
    const { default: QrScanner } = await import(/* @vite-ignore */ qrScannerUrl);

    qrScanner = new QrScanner(qrVideo.value, (result) => {
        if (popupActive.value || result === '' || !result.includes('http')) {
            return;
        }

        const decodedUrl = new URL(result);
        const allowedHosts = ['localhost', 'halloweenhaus-schmalenbeck.de', 'halloweenhaus-website.ddev.site', 'ottos-halloweenhaus.de'];
        if (!allowedHosts.includes(decodedUrl.hostname)) {
            return;
        }

        navigator.vibrate?.(600);
        const urlParts = decodedUrl.pathname.split('/');
        checkCode(urlParts[urlParts.length - 1]);
    });
    qrScanner.start();
});

onBeforeUnmount(() => {
    qrScanner?.destroy();
    clearInterval(timeDifferenceInterval);
    clearInterval(clockInterval);
});
</script>

<template>
    <div class="section_content">
        <div class="section_qrcodescan">
            <video ref="qrVideo" id="qrcode-video"></video>
            <div class="scan-region-highlight" style="position: absolute; pointer-events: none;">
                <svg class="scan-region-highlight-svg" viewBox="0 0 238 238" preserveAspectRatio="none" style="position:absolute;width:100%;left:0;top:0;fill:none;stroke:#e9b213;stroke-width:4;stroke-linecap:round;stroke-linejoin:round"><path d="M31 2H10a8 8 0 0 0-8 8v21M207 2h21a8 8 0 0 1 8 8v21m0 176v21a8 8 0 0 1-8 8h-21m-176 0H10a8 8 0 0 1-8-8v-21"></path></svg><svg class="code-outline-highlight" preserveAspectRatio="none" style="display:none;width:100%;fill:none;stroke:#e9b213;stroke-width:5;stroke-dasharray:25;stroke-linecap:round;stroke-linejoin:round"><polygon></polygon></svg>
            </div>
            <div class="section_bottombar">
                <div class="clock">
                    <p class="clock_text">{{ clockText }}</p>
                </div>
                <div class="logo">
                    <svg
                        class="header_icon"
                        width="100%"
                        viewBox="0 0 52.916666 52.916666"
                        version="1.1"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:svg="http://www.w3.org/2000/svg"
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
                </div>
            </div>
        </div>

        <div class="section_manualcode">
            <input
                v-model="manualCode"
                type="text"
                class="manualcode_input"
                placeholder="Check-In-Code"
                maxlength="8"
                autocapitalize="characters"
                autocomplete="off"
                @keydown.enter="submitManualCode"
            >
            <a class="manualcode_button" @click="submitManualCode">Prüfen</a>
        </div>

        <div class="section_counter">
            <div class="section_showtype">
                <div class="showtype_highlight" :style="{ transform: `translateY(${showTypeIndex * 100}%)` }"></div>
                <img :src="iconScary" class="showtype_icon" :class="{ active: showType === 'Scary' }" alt="Scary" @click="showTypeIndex = 0">
                <img :src="iconMagic" class="showtype_icon" :class="{ active: showType === 'Magic' }" alt="Magic" @click="showTypeIndex = 1">
                <img :src="iconEmpty" class="showtype_icon" :class="{ active: showType === 'Empty' }" alt="Empty" @click="showTypeIndex = 2">
            </div>

            <a class="section_counter_button button_enterShow" @click="enterShow">
                <img class="button_icon" :src="iconEnter" alt="">
            </a>
            <a class="section_counter_button button_increaseSQ" @click="increaseSQ">
                <img class="button_icon" :src="iconIncrease" alt="">
            </a>
            <a class="section_counter_button button_decreaseSQ" @click="decreaseSQ">
                <img class="button_icon" :src="iconDecrease" alt="">
            </a>
        </div>

        <div class="section_numbers">
            <div class="section_numbers_entry">
                <img class="numbers_icon" :src="iconVirtualQueue" alt="">
                <p class="numbers_vq">{{ amountVQ }}</p>
            </div>
            <div class="section_numbers_entry">
                <img class="numbers_icon" :src="iconStandbyQueue" alt="">
                <p class="numbers_sq">{{ amountSQ }}</p>
            </div>
            <div class="section_numbers_entry">
                <img class="numbers_icon" :src="iconQueueTotal" alt="">
                <p class="numbers_tt">{{ amountTT }}</p>
            </div>
        </div>

        <div class="section_popup" :style="{ display: popupActive ? 'block' : 'none' }">
            <a class="section_popup_text section_popup_close" @click="closePopup">X</a>
            <p class="section_popup_text section_popup_client_message" :class="popupState">{{ message }}</p>
            <template v-if="popupValid">
                <p class="section_popup_text section_popup_client_name">
                    {{ name }} ({{ groupSize }} {{ groupSize > 1 ? 'Personen' : 'Person' }})
                </p>
                <hr>
                <p class="section_popup_text section_popup_client_event">{{ eventTitle }}</p>
                <p class="section_popup_text section_popup_client_timeslot">{{ timeSlotText }}</p>
                <hr>
                <p class="section_popup_text section_popup_client_timedifference">{{ timeDifferenceText }}</p>
                <div class="section_people_helper">
                    <img
                        v-for="n in groupSize"
                        :key="n"
                        :src="iconPerson"
                        class="section_people_helper_person"
                        alt=""
                    >
                </div>
                <div class="section_popup_buttons" style="display: flex;">
                    <a class="section_popup_button button_deleteTicket" @click="cancelTicket">Löschen</a>
                    <a class="section_popup_button button_checkinGuest" @click="checkinGuest">Einlass</a>
                </div>
            </template>
        </div>

        <div class="section_loading" :style="{ display: loading ? 'flex' : 'none' }">
            <p>Loading...</p>
        </div>
    </div>
</template>
