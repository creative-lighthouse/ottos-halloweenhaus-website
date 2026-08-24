<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import YearFilter from './components/YearFilter.vue';
import MetricChart from './components/MetricChart.vue';
import OriginBarChart from './components/OriginBarChart.vue';
import EntryMethodChart from './components/EntryMethodChart.vue';
import DonationSourceChart from './components/DonationSourceChart.vue';
import AttendanceChart from './components/AttendanceChart.vue';
import FeedbackComments from './components/FeedbackComments.vue';
import { ALL_DAYS, availableHourlyDays, filterDashboardToDay, formatDayLabel } from './dashboardFilters';

const REFRESH_INTERVAL_MS = 30000;

const availableYears = ref([]);
const selectedYears = ref([]);
const dashboard = ref(null);
const hourlyDay = ref(ALL_DAYS);

// The day picker sticks right below the year filter, so its offset needs the filter's
// actual rendered height (it can wrap to multiple rows depending on how many years
// exist and the viewport width) rather than a guessed constant.
const yearFilterEl = ref(null);
const yearFilterHeight = ref(0);
let yearFilterObserver = null;

let refreshTimer = null;

async function fetchAvailableYears() {
    const response = await fetch('./api/statistics?type=AvailableYears');
    availableYears.value = await response.json();
}

async function fetchDashboard() {
    const years = selectedYears.value.join(',');
    const response = await fetch(`./api/statistics?type=Dashboard&years=${encodeURIComponent(years)}`);
    dashboard.value = await response.json();

    // Default/fallback is the summed-per-hour view; only reset to it if the previously
    // selected specific day no longer has data.
    const days = availableHourlyDays(dashboard.value);
    if (hourlyDay.value !== ALL_DAYS && !days.includes(hourlyDay.value)) {
        hourlyDay.value = ALL_DAYS;
    }
}

const hourlyDays = computed(() => [ALL_DAYS, ...availableHourlyDays(dashboard.value)]);
// Hourly charts only ever show one day at a time (max 24 steps), scoped separately
// from the daily charts which keep using the full dashboard.
const hourlyDashboard = computed(() => filterDashboardToDay(dashboard.value, hourlyDay.value));
// Reference date for the charts' fixed 00:00-23:00 x-axis - the aggregated ALL_DAYS
// view is keyed on the "01-01" placeholder date (see dashboardFilters.js).
const hourlyAxisDay = computed(() => (hourlyDay.value === ALL_DAYS ? '01-01' : hourlyDay.value));

watch(selectedYears, fetchDashboard, { deep: true });

onMounted(async () => {
    await fetchAvailableYears();
    // Default to every year with data selected - assigning here (rather than calling
    // fetchDashboard directly) lets the watch above do the single initial fetch.
    selectedYears.value = availableYears.value.length ? [...availableYears.value] : [new Date().getFullYear()];
    refreshTimer = setInterval(fetchDashboard, REFRESH_INTERVAL_MS);

    if (yearFilterEl.value?.$el) {
        yearFilterObserver = new ResizeObserver(([entry]) => {
            yearFilterHeight.value = entry.contentRect.height;
        });
        yearFilterObserver.observe(yearFilterEl.value.$el);
    }
});

onBeforeUnmount(() => {
    if (refreshTimer) clearInterval(refreshTimer);
    yearFilterObserver?.disconnect();
});
</script>

<template>
    <div class="section_content" :style="{ '--year-filter-height': yearFilterHeight + 'px' }">
        <h1>Statistiken</h1>

        <YearFilter ref="yearFilterEl" v-model="selectedYears" :years="availableYears" />

        <template v-if="dashboard">
            <div class="statistics_hero">
                <p class="statistics_hero_value">{{ dashboard.Combined.TotalGuests.TT }}</p>
                <p class="statistics_hero_label">Gäste gesamt (gewählte Jahre)</p>
            </div>

            <hr>
            <h2>Einlass</h2>
            <div class="statistics_grid">
                <div class="statistics_card statistics_card--wide">
                    <h3>Virtual Queue vs. Standby Queue</h3>
                    <EntryMethodChart :by-year="dashboard.ByYear" :selected-years="selectedYears" />
                </div>
            </div>

            <hr>
            <h2>Registrierungen</h2>
            <div class="statistics_grid">
                <div class="statistics_card">
                    <h3>Herkunft pro PLZ</h3>
                    <OriginBarChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="RegistrationOriginByZIP"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Registrierungen pro Tag</h3>
                    <MetricChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="RegistrationsPerDay"
                        label="Registrierungen"
                        chart-type="line"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Gäste pro Tag</h3>
                    <MetricChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="GuestsPerDay"
                        value-key="TT"
                        label="Gäste"
                        chart-type="bar"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Registriert vs. eingecheckt (No-Shows)</h3>
                    <AttendanceChart :by-year="dashboard.ByYear" :combined="dashboard.Combined" :selected-years="selectedYears" />
                </div>
            </div>

            <hr>
            <h2>Punschbrunnen & Spenden</h2>
            <div class="statistics_grid">
                <div class="statistics_card">
                    <h3>Verkäufe pro Tag</h3>
                    <MetricChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="SalesPerDay"
                        label="Verkäufe"
                        chart-type="bar"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Einnahmen pro Tag</h3>
                    <MetricChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="ProfitsPerDay"
                        label="Einnahmen"
                        unit=" €"
                        chart-type="line"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Spenden nach Quelle</h3>
                    <DonationSourceChart :combined="dashboard.Combined" />
                </div>
            </div>

            <hr>
            <h2>Feedback</h2>
            <div class="statistics_grid">
                <div class="statistics_card">
                    <h3>Durchschnittliche Bewertung</h3>
                    <MetricChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="FeedbackRatingPerDay"
                        value-key="AverageStars"
                        label="Bewertung"
                        unit=" Sterne"
                        chart-type="line"
                        :y-min="0"
                        :y-max="5"
                        context-value-key="Count"
                        context-label="Anzahl Bewertungen"
                    />
                </div>

                <div class="statistics_card">
                    <h3>Herkunft pro PLZ</h3>
                    <OriginBarChart
                        :by-year="dashboard.ByYear"
                        :combined="dashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="FeedbackOriginByZIP"
                    />
                </div>

                <div class="statistics_card statistics_card--wide">
                    <h3>Kommentare</h3>
                    <FeedbackComments :by-year="dashboard.ByYear" :combined="dashboard.Combined" :selected-years="selectedYears" />
                </div>
            </div>

            <hr>
            <h2>Stündliche Statistiken</h2>
            <div class="statistics_daypicker">
                <label for="statistics-hourly-day">Tag</label>
                <select id="statistics-hourly-day" v-model="hourlyDay">
                    <option v-for="day in hourlyDays" :key="day" :value="day">{{ formatDayLabel(day) }}</option>
                </select>
            </div>
            <div class="statistics_grid">
                <div class="statistics_card statistics_card--wide">
                    <h3>Gäste pro Stunde</h3>
                    <MetricChart
                        :by-year="hourlyDashboard.ByYear"
                        :combined="hourlyDashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="GuestsPerHour"
                        value-key="TT"
                        label="Gäste"
                        chart-type="bar"
                        time-unit="hour"
                        :axis-day="hourlyAxisDay"
                    />
                </div>

                <div class="statistics_card statistics_card--wide">
                    <h3>Registrierungen pro Stunde</h3>
                    <MetricChart
                        :by-year="hourlyDashboard.ByYear"
                        :combined="hourlyDashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="RegistrationsPerHour"
                        label="Registrierungen"
                        chart-type="bar"
                        time-unit="hour"
                        :axis-day="hourlyAxisDay"
                    />
                </div>

                <div class="statistics_card statistics_card--wide">
                    <h3>Verkäufe pro Stunde</h3>
                    <MetricChart
                        :by-year="hourlyDashboard.ByYear"
                        :combined="hourlyDashboard.Combined"
                        :selected-years="selectedYears"
                        :available-years="availableYears"
                        field="SalesPerHour"
                        label="Verkäufe"
                        chart-type="bar"
                        time-unit="hour"
                        :axis-day="hourlyAxisDay"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
