/**
 * Las fechas de los preventivos llegan como "YYYY-MM-DD". Se interpretan
 * como fecha local para que en zonas UTC-5 no se muestre el día anterior.
 */
export function formatPreventiveDate(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CO', { dateStyle: 'medium' }).format(
        new Date(`${date}T00:00:00`),
    );
}

/**
 * Fecha de hoy en la zona horaria del navegador, como "YYYY-MM-DD".
 */
export function todayLocalIso(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${month}-${day}`;
}

/**
 * Fecha y hora (ISO 8601) con el formato de Colombia.
 */
export function formatPreventiveDateTime(date: string | null): string {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
}
