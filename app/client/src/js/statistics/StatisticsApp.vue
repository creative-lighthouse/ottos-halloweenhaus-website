<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import YearFilter from './components/YearFilter.vue';
import MetricChart from './components/MetricChart.vue';
import OriginBarChart from './components/OriginBarChart.vue';
import EntryMethodChart from './components/EntryMethodChart.vue';
import FeedbackComments from './components/FeedbackComments.vue';
import { availableHourlyDays, filterDashboardToDay, formatDayLabel, peakHourlyDay } from './dashboardFilters';

const REFRESH_INTERVAL_MS = 30000;

const availableYears = ref([]);
const selectedYears = ref([]);
const dashboard = ref(null);
const hourlyDay = ref(null);

let refreshTimer = null;

async function fetchAvailableYears() {
    const response = await fetch('./api/statistics?type=AvailableYears');
    availableYears.value = await response.json();
}

async function fetchDashboard() {
    const years = selectedYears.value.join(',');
    const response = await fetch(`./api/statistics?type=Dashboard&years=${encodeURIComponent(years)}`);
    dashboard.value = await response.json();

    const days = availableHourlyDays(dashboard.value);
    if (!hourlyDay.value || !days.includes(hourlyDay.value)) {
        hourlyDay.value = peakHourlyDay(dashboard.value) ?? days[0] ?? null;
    }
}

const hourlyDays = computed(() => availableHourlyDays(dashboard.value));
// Hourly charts only ever show one day at a time (max 24 steps), scoped separately
// from the daily charts which keep using the full dashboard.
const hourlyDashboard = computed(() => filterDashboardToDay(dashboard.value, hourlyDay.value));

watch(selectedYears, fetchDashboard, { deep: true });

onMounted(async () => {
    await fetchAvailableYears();
    // Default to every year with data selected - assigning here (rather than calling
    // fetchDashboard directly) lets the watch above do the single initial fetch.
    selectedYears.value = availableYears.value.length ? [...availableYears.value] : [new Date().getFullYear()];
    refreshTimer = setInterval(fetchDashboard, REFRESH_INTERVAL_MS);
});

onBeforeUnmount(() => {
    if (refreshTimer) clearInterval(refreshTimer);
});
</script>

<template>
    <div class="section_content">
        <h1>Statistiken</h1>

        <YearFilter v-model="selectedYears" :years="availableYears" />

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
            </div>

            <hr>
            <h2>Point of Sale</h2>
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
                        chart-type="line"
                        time-unit="hour"
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
                        chart-type="line"
                        time-unit="hour"
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
                        chart-type="line"
                        time-unit="hour"
                    />
                </div>
            </div>
        </template>
    </div>
</template>
