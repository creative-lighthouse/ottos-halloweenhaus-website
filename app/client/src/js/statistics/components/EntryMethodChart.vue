<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import { CATEGORICAL_COLORS, CHART_AXIS_COLOR, CHART_GRID_COLOR, CHART_TEXT_MUTED, CHART_TEXT_SECONDARY } from '../palette';

const props = defineProps({
    byYear: { type: Object, required: true },
    selectedYears: { type: Array, required: true },
});

const canvasEl = ref(null);
let chart = null;

// Years sit on the x-axis so VQ/SQ land next to each other within each year; VQ/SQ get
// fixed colors (not the per-year palette) so that identity stays consistent across
// year clusters instead of being encoded twice.
function buildDatasets() {
    return [
        {
            label: 'Virtual Queue',
            backgroundColor: CATEGORICAL_COLORS[0],
            data: props.selectedYears.map((year) => props.byYear[year]?.TotalGuests?.VQ ?? 0),
        },
        {
            label: 'Standby Queue',
            backgroundColor: CATEGORICAL_COLORS[1],
            data: props.selectedYears.map((year) => props.byYear[year]?.TotalGuests?.SQ ?? 0),
        },
    ];
}

function render() {
    const datasets = buildDatasets();
    const labels = props.selectedYears.map(String);
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
            plugins: {
                legend: { display: true, labels: { color: CHART_TEXT_SECONDARY } },
            },
            scales: {
                x: {
                    grid: { display: false },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                y: {
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
            },
        },
    });
}

onMounted(render);
watch([() => props.byYear, () => props.selectedYears], render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="statistics_chart">
        <canvas ref="canvasEl"></canvas>
    </div>
</template>
