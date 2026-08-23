<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';
import { CHART_AXIS_COLOR, CHART_GRID_COLOR, CHART_TEXT_MUTED, CHART_TEXT_SECONDARY, colorForYear } from '../palette';

const props = defineProps({
    byYear: { type: Object, required: true },
    combined: { type: Object, required: true },
    selectedYears: { type: Array, required: true },
    availableYears: { type: Array, required: true },
    field: { type: String, required: true },
});

const LEVELS = [
    { key: 'Ort', label: 'Ort' },
    { key: 'Kreis', label: 'Kreis' },
    { key: 'Bundesland', label: 'Bundesland' },
];

const activeLevel = ref('Ort');
const canvasEl = ref(null);
let chart = null;

const UNKNOWN = 'Unbekannt';

function isUnknown(name) {
    return !name || name === UNKNOWN;
}

// Sums every entry that resolves to the exact same name at the active level (e.g.
// several Hamburg PLZ become one "Hamburg" group). A level value that server-side
// fell back to the raw ZIP digits - because the PLZ couldn't be resolved at all, or
// (for Kreis) because the city-state it's in genuinely has no Kreis, e.g. Hamburg,
// Berlin, Bremen - is pooled into "Unbekannt" instead of showing up as its own bar of
// digits.
function groupEntries(entries, level) {
    const grouped = new Map();
    let unknownTotal = 0;

    for (const e of entries) {
        const zip = e.ZIP === '' || e.ZIP == null ? null : String(e.ZIP);
        const resolved = zip && e[level] && e[level] !== zip ? e[level] : null;

        if (!resolved) {
            unknownTotal += e.Number;
            continue;
        }

        const group = grouped.get(resolved) ?? { Number: 0, zips: [] };
        group.Number += e.Number;
        if (level === 'Ort') group.zips.push(zip);
        grouped.set(resolved, group);
    }

    if (unknownTotal > 0) {
        const group = grouped.get(UNKNOWN) ?? { Number: 0, zips: [] };
        group.Number += unknownTotal;
        grouped.set(UNKNOWN, group);
    }

    return grouped;
}

// "Ort" keeps the PLZ in the label, but only while it's still unambiguous (exactly one
// PLZ behind that name across the whole selection) - once several PLZ share a name the
// digits aren't representative anymore, so the bar just carries the name.
function labelFor(level, name, group) {
    return level === 'Ort' && group.zips.length === 1 ? `${group.zips[0]} ${name}` : name;
}

// Sequential = one hue, magnitude via opacity (not a pre-baked light-surface ramp,
// which would go unreadable-dark against this page's near-black background). Used only
// for the single-year view, where there's just one series and no year to color by.
function alphaForValue(value, max) {
    if (!max) return 0.6;
    return 0.35 + 0.65 * (value / max);
}

const isMultiYear = computed(() => props.selectedYears.length > 1);

// Category order (and, for "Ort", label ambiguity) is always judged from the combined
// group across every selected year, so it stays stable while switching years on/off.
const categories = computed(() => {
    const combinedGroup = groupEntries(props.combined[props.field] ?? [], activeLevel.value);
    const names = [...combinedGroup.keys()].sort((a, b) => {
        if (isUnknown(a) !== isUnknown(b)) return isUnknown(a) ? 1 : -1;
        return combinedGroup.get(b).Number - combinedGroup.get(a).Number;
    });
    return { names, combinedGroup };
});

const chartHeight = computed(() => Math.max(300, categories.value.names.length * 28 + 40));

function render() {
    const { names, combinedGroup } = categories.value;
    const labels = names.map((name) => labelFor(activeLevel.value, name, combinedGroup.get(name)));

    let datasets;
    if (isMultiYear.value) {
        datasets = props.selectedYears.map((year) => {
            const yearGroup = groupEntries(props.byYear[year]?.[props.field] ?? [], activeLevel.value);
            return {
                label: String(year),
                data: names.map((name) => yearGroup.get(name)?.Number ?? 0),
                backgroundColor: colorForYear(year, props.availableYears),
            };
        });
    } else {
        const max = Math.max(0, ...names.map((name) => combinedGroup.get(name).Number));
        datasets = [{
            label: 'Anzahl',
            data: names.map((name) => combinedGroup.get(name).Number),
            backgroundColor: names.map((name) => `rgba(57, 135, 229, ${alphaForValue(combinedGroup.get(name).Number, max)})`),
        }];
    }

    if (chart) {
        chart.data.labels = labels;
        chart.data.datasets = datasets;
        chart.options.plugins.legend.display = isMultiYear.value;
        chart.update('none');
        return;
    }

    chart = new Chart(canvasEl.value, {
        type: 'bar',
        data: { labels, datasets },
        options: {
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: { legend: { display: isMultiYear.value, labels: { color: CHART_TEXT_SECONDARY } } },
            scales: {
                x: {
                    grid: { color: CHART_GRID_COLOR },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
                y: {
                    grid: { display: false },
                    border: { color: CHART_AXIS_COLOR },
                    ticks: { color: CHART_TEXT_MUTED },
                },
            },
        },
    });
}

onMounted(render);
watch([() => props.byYear, () => props.combined, () => props.selectedYears, activeLevel], render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div>
        <div class="statistics_tabs">
            <button
                v-for="level in LEVELS"
                :key="level.key"
                type="button"
                class="statistics_tab"
                :class="{ 'is-active': activeLevel === level.key }"
                @click="activeLevel = level.key"
            >
                {{ level.label }}
            </button>
        </div>

        <div class="statistics_chart" :style="{ height: chartHeight + 'px' }">
            <canvas ref="canvasEl"></canvas>
        </div>
    </div>
</template>
