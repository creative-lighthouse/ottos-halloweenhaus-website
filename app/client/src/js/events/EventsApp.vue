<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import CouponStep from './components/CouponStep.vue';
import RegistrationForm from './components/RegistrationForm.vue';

const POLL_INTERVAL_MS = 15000;

const props = defineProps({
    initial: { type: Object, required: true },
    preselectEventId: { type: Number, default: null },
    capacityUrl: { type: String, default: null },
});

const data = props.initial;
const dates = reactive(data.dates ?? []);

// Timeslot lookups so the capacity poll can patch objects in place and keep every
// reference (the selected slot included) reactive. Normal and coupon slots share
// IDs but are distinct objects, so they need distinct indexes.
const normalSlotIndex = new Map();
const couponSlotIndex = new Map();
for (const date of dates) {
    for (const event of date.events) {
        for (const slot of event.timeslots) normalSlotIndex.set(slot.id, slot);
        for (const slot of event.couponTimeslots) couponSlotIndex.set(slot.id, slot);
    }
}

const coupon = reactive({
    code: '',
    valid: false,
    checking: false,
    message: data.usesCoupon ? 'Bitte gib deinen Couponcode ein' : '',
    description: '',
    type: null,
});

const selectedDate = ref(null);
const selectedEvent = ref(null);
const selectedSlot = ref(null);
const groupSize = ref(0);

const step1 = ref(null);
const step2 = ref(null);
const step3 = ref(null);
const step4 = ref(null);
const step5 = ref(null);
const infoDialog = ref(null);

const usesCoupon = computed(() => !!data.usesCoupon);
const showStep1 = computed(() => !usesCoupon.value || coupon.valid);
const showStep2 = computed(() => showStep1.value && !!selectedDate.value);
const showStep3 = computed(() => !!selectedEvent.value);
const showStep4 = computed(() => !!selectedSlot.value);
const showStep5 = computed(() => groupSize.value > 0);

const eventsForDate = computed(() => selectedDate.value?.events ?? []);

const slotsForEvent = computed(() => {
    if (!selectedEvent.value) return [];
    return coupon.valid ? selectedEvent.value.couponTimeslots : selectedEvent.value.timeslots;
});

const noSlotsMessage = computed(() => {
    if (!selectedEvent.value) return false;
    const empty = coupon.valid
        ? selectedEvent.value.noFreeCouponTimeslots
        : selectedEvent.value.noFreeTimeslots;
    return empty;
});

const groupSizes = computed(() => {
    const max = data.maxGroupSize ?? 0;
    const available = selectedSlot.value?.freeCount ?? 0;
    return Array.from({ length: max }, (_, i) => i + 1).map((size) => ({
        size,
        disabled: size > available,
    }));
});

// Fills the timeslot card like a progress bar: the light grey portion grows as
// the slot books up (based on how many places are still free).
function slotFillStyle(slot) {
    const total = Number(slot.totalCount) || 0;
    if (total <= 0) return null;
    const free = Math.min(Math.max(Number(slot.freeCount) || 0, 0), total);
    const filledPercent = ((total - free) / total) * 100;
    return {
        background: `linear-gradient(to right, rgba(255, 255, 255, 0.16) ${filledPercent}%, transparent ${filledPercent}%)`,
    };
}

function scrollTo(stepRef) {
    nextTick(() => {
        const el = stepRef.value;
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
}

function selectDate(date) {
    selectedDate.value = date;
    selectedEvent.value = null;
    selectedSlot.value = null;
    groupSize.value = 0;
    scrollTo(step2);
}

function selectEvent(event) {
    selectedEvent.value = event;
    selectedSlot.value = null;
    groupSize.value = 0;
    scrollTo(step3);
}

function selectSlot(slot) {
    if (slot.full) return;
    selectedSlot.value = slot;
    groupSize.value = 0;
    scrollTo(step4);
}

function selectGroupSize(entry) {
    if (entry.disabled) return;
    groupSize.value = entry.size;
    scrollTo(step5);
}

function onCouponValid() {
    // Coupon just unlocked the date step; nudge the user down to it.
    scrollTo(step1);
}

function onCouponReset() {
    selectedDate.value = null;
    selectedEvent.value = null;
    selectedSlot.value = null;
    groupSize.value = 0;
}

async function fetchCapacity() {
    if (!props.capacityUrl) return;
    try {
        const response = await fetch(props.capacityUrl, { headers: { Accept: 'application/json' } });
        const payload = await response.json();
        const merge = (map, index) => {
            for (const [id, info] of Object.entries(map ?? {})) {
                const slot = index.get(Number(id));
                if (slot) Object.assign(slot, info);
            }
        };
        merge(payload.timeslots, normalSlotIndex);
        merge(payload.couponTimeslots, couponSlotIndex);
    } catch (error) {
        console.error('Kapazitäts-Update fehlgeschlagen', error);
    }
}

let pollTimer = null;

onMounted(() => {
    if (props.preselectEventId) {
        for (const date of dates) {
            const match = date.events.find((event) => event.id === props.preselectEventId);
            if (match) {
                selectedDate.value = date;
                selectedEvent.value = match;
                nextTick(() => scrollTo(step3));
                break;
            }
        }
    }

    if (props.capacityUrl) {
        pollTimer = setInterval(fetchCapacity, POLL_INTERVAL_MS);
    }
});

onBeforeUnmount(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <div class="events_navigator">
        <CouponStep
            v-if="usesCoupon"
            :coupon="coupon"
            :check-url-base="data.eventPageLink"
            @valid="onCouponValid"
            @reset="onCouponReset"
        />

        <Transition name="step">
            <div v-show="showStep1" ref="step1" class="events_navigator_step dates">
                <h2>1. Datum wählen</h2>
                <div class="section_selectablelist">
                    <div
                        v-for="date in dates"
                        :key="date.date"
                        class="date_card"
                        :class="{ selected: selectedDate === date }"
                        @click="selectDate(date)"
                    >
                        <p class="date_card_weekday">{{ date.weekday }}</p>
                        <p class="date_card_day">{{ date.day }}</p>
                        <p class="date_card_month">{{ date.month }}</p>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="step">
            <div v-show="showStep2" ref="step2" class="events_navigator_step events">
                <h2>2. Veranstaltung wählen</h2>
                <div class="section_selectablelist">
                    <div
                        v-for="event in eventsForDate"
                        :key="event.id"
                        class="event_card"
                        :class="{ selected: selectedEvent === event }"
                        @click="selectEvent(event)"
                    >
                        <div class="event_card_image">
                            <img v-if="event.image" :src="event.image" :alt="event.title">
                        </div>
                        <div class="event_card_text">
                            <p class="event_card_title">{{ event.title }}</p>
                            <p class="event_card_duration">Dauer: ca. {{ event.slotDuration }} Minuten</p>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="step">
            <div v-show="showStep3" ref="step3" class="events_navigator_step timeslots">
                <h2>3. Startzeit wählen</h2>
                <div class="section_selectablelist">
                    <p v-if="noSlotsMessage" class="timeslot_card timeslot_card--text">
                        Aktuell sind leider keine freien Zeitslots mehr verfügbar. Besuche uns dennoch
                        gerne über die reguläre Warteschlange.
                    </p>
                    <div
                        v-for="slot in slotsForEvent"
                        :key="slot.id"
                        class="timeslot_card"
                        :class="{ 'timeslot_card--full': slot.full, selected: selectedSlot === slot }"
                        :style="selectedSlot === slot ? null : slotFillStyle(slot)"
                        @click="selectSlot(slot)"
                    >
                        <p class="timeslot_card_time">{{ slot.time }}</p>
                        <p class="timeslot_card_capacity">{{ slot.capacityLabel }}</p>
                    </div>
                </div>
                <p class="timeslot_info_text">
                    Weitere Zeitslots werden regelmäßig freigeschaltet. Unsere reguläre Warteschlange vor
                    Ort hat zusätzlich geöffnet und benötigt keine Buchung.
                </p>
                <button class="timeslot_dialog_button" type="button" @click="infoDialog?.showModal()">
                    Weitere Informationen
                </button>
                <dialog ref="infoDialog" class="timeslot_dialog">
                    <h1 class="text-center">Hinweis zu den Zeitslots der Halloween Shows</h1>
                    <p class="text-center">
                        Sollte kein passender Zeitslot verfügbar sein,
                        <strong>versuche es bitte später erneut oder komm einfach vorbei</strong>
                        und stelle dich in die <strong>reguläre Warteschlange.</strong>
                    </p>
                    <p class="text-center">
                        Die <strong>digitalen Zeitslots für die Halloween-Shows</strong> sind aufgrund der
                        hohen Nachfrage limitiert. Regelmäßig werden neue Plätze freigeschaltet.
                    </p>
                    <p class="text-center">
                        Sollte deine Gruppe <strong>größer als {{ data.maxGroupSize }} Personen sein</strong>,
                        buche gerne zwei aufeinander folgende Zeitslots und gib am Eingang Bescheid. Ihr könnt
                        dann auch gemeinsam die Show genießen.
                    </p>
                    <p class="text-center">
                        Die <strong>Behind the Scenes Touren</strong> haben keine reguläre Warteschlange und
                        sind auf die angegebenen Plätze limitiert.
                    </p>
                    <p class="text-center">
                        Weitere Informationen zu den Zeitslots, unserer virtuellen Warteschlange, dem Einlass
                        und der Show findest Du auch in unseren <a href="/faq">FAQs</a>.
                    </p>
                    <button class="timeslot_dialog_button" type="button" @click="infoDialog?.close()">
                        Schließen
                    </button>
                </dialog>
            </div>
        </Transition>

        <Transition name="step">
            <div v-show="showStep4" ref="step4" class="events_navigator_step groupsize">
                <h2>4. Gruppengröße wählen</h2>
                <div class="section_selectablelist">
                    <a
                        v-for="entry in groupSizes"
                        :key="entry.size"
                        class="groupsize_button"
                        :class="{ selected: groupSize === entry.size, hidden: entry.disabled }"
                        @click="selectGroupSize(entry)"
                    >
                        {{ entry.size === 1 ? '1 Person' : entry.size + ' Personen' }}
                    </a>
                </div>
            </div>
        </Transition>

        <Transition name="step">
            <div v-show="showStep5" ref="step5" class="events_navigator_step form">
                <h2>5. Anmelden</h2>
                <RegistrationForm
                    v-if="selectedEvent && selectedSlot"
                    :action-url="data.registrationFormUrl"
                    :security-id="data.securityID"
                    :event-id="selectedEvent.id"
                    :timeslot-id="selectedSlot.id"
                    :group-size="groupSize"
                    :coupon-code="coupon.valid ? coupon.code : ''"
                    :event-title="selectedEvent.title"
                />
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.step-enter-active,
.step-leave-active {
    transition: opacity 0.2s ease-in-out, max-height 0.2s ease-in-out;
    overflow: hidden;
}

@media (prefers-reduced-motion: reduce) {
    .step-enter-active,
    .step-leave-active {
        transition: none;
    }
}

.step-enter-from,
.step-leave-to {
    opacity: 0;
    max-height: 0;
}

.step-enter-to,
.step-leave-from {
    opacity: 1;
    max-height: 1200px;
}
</style>
