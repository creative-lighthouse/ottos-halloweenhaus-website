<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    byYear: { type: Object, required: true },
    combined: { type: Object, required: true },
    selectedYears: { type: Array, required: true },
});

const activeTab = ref(props.selectedYears.length > 1 ? 'combined' : props.selectedYears[0]);

watch(
    () => props.selectedYears,
    (years) => {
        const validKeys = years.length > 1 ? ['combined', ...years] : years;
        if (!validKeys.includes(activeTab.value)) {
            activeTab.value = years.length > 1 ? 'combined' : years[0];
        }
    }
);

const tabs = computed(() => {
    const yearTabs = props.selectedYears.map((year) => ({ key: year, label: String(year) }));
    return props.selectedYears.length > 1 ? [{ key: 'combined', label: 'Kombiniert' }, ...yearTabs] : yearTabs;
});

const entries = computed(() => {
    if (activeTab.value === 'combined') return props.combined.FeedbackComments ?? [];
    return props.byYear[activeTab.value]?.FeedbackComments ?? [];
});

// A single-year tab already gives the year via its label, so the day alone is
// unambiguous there; the combined tab mixes years, so it needs the year spelled out.
function displayDay(item) {
    return activeTab.value === 'combined' && item.Year ? `${item.Day}${item.Year}` : item.Day;
}
</script>

<template>
    <div>
        <div class="statistics_tabs" v-if="tabs.length > 1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                class="statistics_tab"
                :class="{ 'is-active': activeTab === tab.key }"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <ul class="statistics_comments">
            <li v-for="(item, index) in entries" :key="index">
                <p class="statistics_comments_text">{{ item.Comment }}</p>
                <p class="statistics_comments_day">{{ displayDay(item) }}</p>
            </li>
        </ul>
    </div>
</template>
