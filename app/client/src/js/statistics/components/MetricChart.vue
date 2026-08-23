<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-moment';
import { buildYearDatasets } from '../charts';
import { CHART_AXIS_COLOR, CHART_GRID_COLOR, CHART_TEXT_MUTED, CHART_TEXT_SECONDARY } from '../palette';

const props = defineProps({
    byYear: { type: Object, required: true },
    combined: { type: Object, required: true },
    selectedYears: { type: Array, required: true },
    availableYears: { type: Array, required: true },
    field: { type: String, required: true },
    valueKey: { type: String, default: null },
    label: { type: String, required: true },
    unit: { type: String, default: '' },
    chartType: { type: String, default: 'line' },
    timeUnit: { type: String, default: 'day' },
});

const canvasEl = ref(null);
let chart = null;

function extractByYearData() {
    const result = {};
    for (const year of props.selectedYears) {
        result[year] = props.byYear[year]?.[props.field] ?? {};
    }
    return result;
}

function buildDatasets() {
    return buildYearDatasets({
        byYearData: extractByYearData(),
        combinedData: props.combined?.[props.field] ?? {},
        selectedYears: props.selectedYears,
        availableYears: props.availableYears,
        label: props.label,
        valueKey: props.valueKey,
        type: props.chartType,
    });
}

const displayFormat = props.timeUnit === 'hour' ? 'DD.MM. HH:00' : 'DD.MM.';

function render() {
    const datasets = buildDatasets();
    if (chart) {
        chart.data.datasets = datasets;
        // 'none' skips the redraw animation so the periodic auto-refresh doesn't
        // make the chart visibly flash/reset every time.
        chart.update('none');
        return;
    }
    chart = new Chart(canvasEl.value, {
        type: props.chartType,
        data: { datasets },
        options: {
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            scales: {
                x: {
                    type: 'time',
                    time: { unit: props.timeUnit, displayFormats: { [props.timeUnit]: displayFormat }, tooltipFormat: displayFormat },
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                y: {
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
            },
            plugins: {
                legend: { labels: { color: CHART_TEXT_SECONDARY } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.formattedValue}${props.unit}`,
                    },
                },
            },
        },
    });
}

onMounted(render);
watch(() => [props.byYear, props.combined, props.selectedYears], render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="statistics_chart">
        <canvas ref="canvasEl"></canvas>
    </div>
</template>
