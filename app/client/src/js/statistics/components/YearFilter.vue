<script setup>
import { colorForYear } from '../palette';

const props = defineProps({
    years: { type: Array, default: () => [] },
    modelValue: { type: Array, required: true },
});
const emit = defineEmits(['update:modelValue']);

function isSelected(year) {
    return props.modelValue.includes(year);
}

function toggle(year) {
    if (isSelected(year)) {
        // At least one year must stay selected.
        if (props.modelValue.length === 1) return;
        emit('update:modelValue', props.modelValue.filter((y) => y !== year));
    } else {
        emit('update:modelValue', [...props.modelValue, year]);
    }
}

function selectAll() {
    emit('update:modelValue', [...props.years]);
}
</script>

<template>
    <div class="statistics_filter">
        <button
            v-for="year in years"
            :key="year"
            type="button"
            class="statistics_yearchip"
            :class="{ 'is-active': isSelected(year) }"
            :style="{ '--chip-color': colorForYear(year, years) }"
            @click="toggle(year)"
        >
            <span class="statistics_yearchip_dot"></span>
            {{ year }}
        </button>
        <button type="button" class="statistics_yearchip statistics_yearchip--all" @click="selectAll">
            Alle
        </button>
    </div>
</template>
