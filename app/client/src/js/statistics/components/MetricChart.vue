<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-moment';
import { buildYearDatasets, toSeries } from '../charts';
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
    // "MM-DD" reference date for hourly charts, so the x-axis always spans the full
    // 00:00-23:00 day (not just whatever hours happen to have data) and every hourly
    // chart ends up the same width regardless of how sparse its data is.
    axisDay: { type: String, default: null },
    yMin: { type: Number, default: null },
    yMax: { type: Number, default: null },
    // Optional secondary bar series plotted on its own right-hand axis, for context
    // metrics that share the same day/hour keys but a different unit (e.g. how many
    // ratings a "Durchschnittliche Bewertung" line is actually based on).
    contextValueKey: { type: String, default: null },
    contextLabel: { type: String, default: null },
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
    const datasets = buildYearDatasets({
        byYearData: extractByYearData(),
        combinedData: props.combined?.[props.field] ?? {},
        selectedYears: props.selectedYears,
        availableYears: props.availableYears,
        label: props.label,
        valueKey: props.valueKey,
        type: props.chartType,
    });

    // Context bars go first in the array so the metric line(s) draw on top of them,
    // and use the combined (all-selected-years) data rather than one bar set per year
    // to keep the chart legible.
    if (props.contextValueKey) {
        datasets.unshift({
            type: 'bar',
            label: props.contextLabel,
            yAxisID: 'y1',
            backgroundColor: 'rgba(195, 194, 183, 0.25)',
            borderWidth: 0,
            data: toSeries(props.combined?.[props.field] ?? {}, props.contextValueKey),
        });
    }

    return datasets;
}

// Hourly charts are always scoped to one already-selected day (see dashboardFilters.js),
// so the date would just repeat on every tick - only the hour is worth showing.
const displayFormat = props.timeUnit === 'hour' ? 'HH:00' : 'DD.MM.';

function hourAxisBounds() {
    if (props.timeUnit !== 'hour' || !props.axisDay) return {};
    const [month, day] = props.axisDay.split('-').map(Number);
    return {
        min: new Date(2000, month - 1, day, 0),
        max: new Date(2000, month - 1, day, 23),
    };
}

function render() {
    const datasets = buildDatasets();
    if (chart) {
        chart.data.datasets = datasets;
        // axisDay can change (switching the hourly day picker) without touching
        // byYear/combined identity elsewhere, so the bounds need refreshing here too,
        // not just at chart construction below.
        Object.assign(chart.options.scales.x, hourAxisBounds());
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
                    ...hourAxisBounds(),
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                y: {
                    min: props.yMin ?? undefined,
                    max: props.yMax ?? undefined,
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                ...(props.contextValueKey ? {
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        grid: { display: false },
                        border: { color: CHART_AXIS_COLOR },
                        ticks: { color: CHART_TEXT_MUTED, precision: 0 },
                    },
                } : {}),
            },
            plugins: {
                legend: { labels: { color: CHART_TEXT_SECONDARY } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${ctx.formattedValue}${ctx.dataset.yAxisID === 'y1' ? '' : props.unit}`,
                    },
                },
            },
        },
    });
}

onMounted(render);
watch(() => [props.byYear, props.combined, props.selectedYears, props.axisDay], render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div class="statistics_chart">
        <canvas ref="canvasEl"></canvas>
    </div>
</template>
