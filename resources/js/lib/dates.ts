/**
 * Read a `Y-m-d` value as a local date, so it never shifts a day across time zones.
 */
export function parseDateValue(value: string): Date {
    const [year, month, day] = value.split('-').map(Number);

    return new Date(year, month - 1, day);
}

/**
 * Write a local date back as the `Y-m-d` value the server expects.
 */
export function toDateValue(date: Date): string {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

/**
 * Format a `Y-m-d` value without shifting it across time zones.
 */
export function formatDate(value: string): string {
    return parseDateValue(value).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

/**
 * Format a timestamp in the viewer's time zone.
 */
export function formatTimestamp(timestamp: string): string {
    return new Date(timestamp).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
