const HOURLY_FIELDS = ['GuestsPerHour', 'RegistrationsPerHour', 'SalesPerHour'];

function dayOf(hourKey) {
    return hourKey.split(' ')[0];
}

export function formatDayLabel(day) {
    if (!day) return '';
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

// Sensible default: the day with the most guests, so the hourly section opens on the
// actual event peak rather than an arbitrary first day.
export function peakHourlyDay(dashboard) {
    const guestsPerHour = dashboard?.Combined?.GuestsPerHour ?? {};
    const totals = {};
    for (const [key, value] of Object.entries(guestsPerHour)) {
        const day = dayOf(key);
        totals[day] = (totals[day] ?? 0) + (value.TT ?? 0);
    }
    let bestDay = null;
    let bestTotal = -1;
    for (const [day, total] of Object.entries(totals)) {
        if (total > bestTotal) {
            bestTotal = total;
            bestDay = day;
        }
    }
    return bestDay;
}

function filterSectionToDay(section, day) {
    const result = { ...section };
    for (const field of HOURLY_FIELDS) {
        const dict = section[field] ?? {};
        result[field] = Object.fromEntries(Object.entries(dict).filter(([key]) => dayOf(key) === day));
    }
    return result;
}

// Restricts a full dashboard payload's hourly fields to one calendar day, so the
// hourly charts show at most 24 steps instead of every day concatenated.
export function filterDashboardToDay(dashboard, day) {
    if (!dashboard || !day) return { Combined: {}, ByYear: {} };
    const byYear = {};
    for (const [year, section] of Object.entries(dashboard.ByYear)) {
        byYear[year] = filterSectionToDay(section, day);
    }
    return {
        Combined: filterSectionToDay(dashboard.Combined, day),
        ByYear: byYear,
    };
}
