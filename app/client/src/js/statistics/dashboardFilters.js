const HOURLY_FIELDS = ['GuestsPerHour', 'RegistrationsPerHour', 'SalesPerHour'];

// Sentinel "day" selecting the summed-per-hour view (all calendar days folded onto one
// 0-23 axis) instead of one specific date.
export const ALL_DAYS = '__all__';

function dayOf(hourKey) {
    return hourKey.split(' ')[0];
}

export function formatDayLabel(day) {
    if (!day) return '';
    if (day === ALL_DAYS) return 'Alle Tage (Summe pro Stunde)';
    const [month, date] = day.split('-');
    return `${parseInt(date, 10)}.${parseInt(month, 10)}.`;
}

// Every distinct "MM-DD" that has any hourly data, across all three hourly metrics.
export function availableHourlyDays(dashboard) {
    if (!dashboard) return [];
    const days = new Set();
    for (const field of HOURLY_FIELDS) {
        for (const key of Object.keys(dashboard.Combined?.[field] ?? {})) {
            days.add(dayOf(key));
        }
    }
    return [...days].sort();
}

function filterSectionToDay(section, day) {
    const result = { ...section };
    for (const field of HOURLY_FIELDS) {
        const dict = section[field] ?? {};
        result[field] = Object.fromEntries(Object.entries(dict).filter(([key]) => dayOf(key) === day));
    }
    return result;
}

// Folds every calendar day onto one 0-23 hour axis by summing same-hour values across
// days (a fixed placeholder date keeps the "MM-DD HH:00" key shape the charts expect).
// Values are either plain numbers or {VQ, SQ, TT}-style triplets, so both get merged.
function sumHourValues(a, b) {
    if (typeof b === 'object' && b !== null) {
        const result = { ...a };
        for (const [key, value] of Object.entries(b)) {
            result[key] = (result[key] ?? 0) + value;
        }
        return result;
    }
    return (a ?? 0) + b;
}

function aggregateSectionAllDays(section) {
    const result = { ...section };
    for (const field of HOURLY_FIELDS) {
        const dict = section[field] ?? {};
        const byHour = {};
        for (const [key, value] of Object.entries(dict)) {
            const hour = key.split(' ')[1] ?? '00:00';
            const newKey = `01-01 ${hour}`;
            byHour[newKey] = sumHourValues(byHour[newKey], value);
        }
        // Object key order here is "whichever hour was first seen while scanning the
        // days", not chronological - and Chart.js line/bar datasets draw points in
        // array order, not sorted by x value, so an unsorted dict produces a zigzagging
        // line. Re-sort by hour before handing it back.
        result[field] = Object.fromEntries(Object.entries(byHour).sort(([a], [b]) => a.localeCompare(b)));
    }
    return result;
}

// Restricts a full dashboard payload's hourly fields to one calendar day (or, for
// ALL_DAYS, sums every day onto one hour axis), so the hourly charts show at most 24
// steps instead of every day concatenated.
export function filterDashboardToDay(dashboard, day) {
    if (!dashboard || !day) return { Combined: {}, ByYear: {} };

    const sectionFilter = day === ALL_DAYS ? aggregateSectionAllDays : (section) => filterSectionToDay(section, day);

    const byYear = {};
    for (const [year, section] of Object.entries(dashboard.ByYear)) {
        byYear[year] = sectionFilter(section);
    }
    return {
        Combined: sectionFilter(dashboard.Combined),
        ByYear: byYear,
    };
}
