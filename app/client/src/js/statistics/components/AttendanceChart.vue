<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import { CATEGORICAL_COLORS, CHART_AXIS_COLOR, CHART_GRID_COLOR, CHART_TEXT_MUTED, CHART_TEXT_SECONDARY } from '../palette';

const props = defineProps({
    byYear: { type: Object, required: true },
    combined: { type: Object, required: true },
    selectedYears: { type: Array, required: true },
});

const canvasEl = ref(null);
let chart = null;

function attendanceFor(section) {
    return {
        Registered: section?.RegistrationAttendance?.Registered ?? 0,
        CheckedIn: section?.RegistrationAttendance?.CheckedIn ?? 0,
        NoShow: section?.RegistrationAttendance?.NoShow ?? 0,
    };
}

// Years sit on the x-axis like EntryMethodChart, plus - once more than one year is
// selected - a trailing "Durchschnitt" category averaged from the combined (summed)
// totals, since RegistrationAttendance itself stays a per-year total, not a rate.
function buildLabelsAndValues() {
    const labels = props.selectedYears.map(String);
    const values = props.selectedYears.map((year) => attendanceFor(props.byYear[year]));

    if (props.selectedYears.length > 1) {
        const combined = attendanceFor(props.combined);
        const count = props.selectedYears.length;
        labels.push('Durchschnitt');
        values.push({
            Registered: Math.round(combined.Registered / count),
            CheckedIn: Math.round(combined.CheckedIn / count),
            NoShow: Math.round(combined.NoShow / count),
        });
    }

    return { labels, values };
}

// Stacked so the bar's total height is Registered (CheckedIn + NoShow always sums back
// to it), rather than a separate same-height bar next to its own two parts.
function buildDatasets(values) {
    return [
        {
            label: 'Eingecheckt',
            backgroundColor: CATEGORICAL_COLORS[2],
            data: values.map((v) => v.CheckedIn),
        },
        {
            label: 'No-Shows',
            backgroundColor: CATEGORICAL_COLORS[7],
            data: values.map((v) => v.NoShow),
        },
    ];
}

function render() {
    const { labels, values } = buildLabelsAndValues();
    const datasets = buildDatasets(values);
    if (chart) {
        chart.data.labels = labels;
        chart.data.datasets = datasets;
        chart.update('none');
        return;
    }

    chart = new Chart(canvasEl.value, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: true, labels: { color: CHART_TEXT_SECONDARY } },
                tooltip: {
                    callbacks: {
                        footer: (items) => `Registriert: ${items.reduce((sum, item) => sum + item.parsed.y, 0)}`,
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    grid: { display: false },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                y: {
                    stacked: true,
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED, precision: 0 },
                },
            },
        },
    });
}

onMounted(render);
watch([() => props.byYear, () => props.combined, () => props.selectedYears], render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="statistics_chart">
        <canvas ref="canvasEl"></canvas>
    </div>
</template>
