// Dark-mode categorical palette, validated against this site's --ColorBackground (#151515)
// with the dataviz skill's validate_palette.js (all 6 checks pass). Fixed order = the
// CVD-safety mechanism, so slots are never reassigned/cycled per chart.
export const CATEGORICAL_COLORS = [
    '#3987e5', // 1 blue
    '#d95926', // 2 orange
    '#199e70', // 3 aqua
    '#c98500', // 4 yellow
    '#d55181', // 5 magenta
    '#008300', // 6 green
    '#9085e9', // 7 violet
    '#e66767', // 8 red
];

// Neutral, dashed - marks the "Summe" series as a derived aggregate rather than "just
// another year", per palette.md's chart chrome ink.
export const COMBINED_COLOR = '#c3c2b7';

export const CHART_GRID_COLOR = '#2c2c2a';
export const CHART_AXIS_COLOR = '#383835';
export const CHART_TEXT_MUTED = '#898781';
export const CHART_TEXT_SECONDARY = '#c3c2b7';

/**
 * A year always gets the same color regardless of which other years are toggled on -
 * the index is the year's position in the full available-years list, not the
 * currently-selected subset ("color follows the entity, never its rank").
 */
export function colorForYear(year, availableYears) {
    const sorted = [...availableYears].map(Number).sort((a, b) => a - b);
    const index = sorted.indexOf(Number(year));
    return CATEGORICAL_COLORS[(index === -1 ? 0 : index) % CATEGORICAL_COLORS.length];
}
