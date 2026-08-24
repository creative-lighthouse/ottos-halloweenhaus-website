<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import { CATEGORICAL_COLORS, CHART_AXIS_COLOR, CHART_GRID_COLOR, CHART_TEXT_MUTED } from '../palette';

const props = defineProps({
    combined: { type: Object, required: true },
});

const canvasEl = ref(null);
let chart = null;

// Sources are free text, not a fixed set like VQ/SQ, so labels are sorted alphabetically
// for a stable color assignment rather than by amount (which would reshuffle colors as
// totals change).
function buildData() {
    const bySource = props.combined?.DonationsBySource ?? {};
    const labels = Object.keys(bySource).sort((a, b) => a.localeCompare(b, 'de'));
    return {
        labels,
        amounts: labels.map((label) => bySource[label]),
        colors: labels.map((_, index) => CATEGORICAL_COLORS[index % CATEGORICAL_COLORS.length]),
    };
}

function render() {
    const { labels, amounts, colors } = buildData();
    const dataset = {
        label: 'Spenden',
        data: amounts,
        backgroundColor: colors,
    };

    if (chart) {
        chart.data.labels = labels;
        chart.data.datasets = [dataset];
        chart.update('none');
        return;
    }

    chart = new Chart(canvasEl.value, {
        type: 'bar',
        data: { labels, datasets: [dataset] },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.formattedValue} €`,
                    },
                },
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
                    ticks: { color: CHART_TEXT_MUTED, callback: (value) => `${value} €` },
                },
            },
        },
    });
}

onMounted(render);
watch(() => props.combined, render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="statistics_chart">
        <canvas ref="canvasEl"></canvas>
    </div>
</template>
