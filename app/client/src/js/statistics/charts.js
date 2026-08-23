import { colorForYear, COMBINED_COLOR } from './palette';

// Keys are "MM-DD" or "MM-DD HH:00" (year-independent - see ApiPageController), plotted
// on a fixed reference year so the same calendar day/hour across different years lines
// up on the same x position.
function toPoint(key, value) {
    const [datePart, timePart] = key.split(' ');
    const [month, day] = datePart.split('-').map(Number);
    const hour = timePart ? Number(timePart.split(':')[0]) : 0;
    return { x: new Date(2000, month - 1, day, hour), y: value };
}

function toSeries(dict, valueKey) {
    return Object.entries(dict || {}).map(([key, raw]) => toPoint(key, valueKey ? raw[valueKey] : raw));
}

/**
 * Builds Chart.js datasets for one metric: one series per selected year (stable color
 * per year) plus - once more than one year is selected - a dashed "Durchschnitt" series
 * from the averaged data.
 */
export function buildYearDatasets({ byYearData, combinedData, selectedYears, availableYears, label, valueKey = null, type = 'line' }) {
    const datasets = selectedYears.map((year) => ({
        type,
        label: `${label} ${year}`,
        borderColor: colorForYear(year, availableYears),
        backgroundColor: colorForYear(year, availableYears),
        borderWidth: 2,
        pointRadius: 3,
        pointHoverRadius: 6,
        data: toSeries(byYearData[year], valueKey),
    }));

    if (selectedYears.length > 1) {
        datasets.push({
            type: 'line',
            label: `${label} Durchschnitt`,
            borderColor: COMBINED_COLOR,
            backgroundColor: COMBINED_COLOR,
            borderDash: [6, 4],
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 5,
            data: toSeries(combinedData, valueKey),
        });
    }

    return datasets;
}
