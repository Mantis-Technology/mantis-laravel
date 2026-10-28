import { Link } from '@inertiajs/react';
import { AlertTriangle, CalendarCheck } from 'lucide-react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    formatPreventiveDate,
    formatPreventiveDateTime,
} from '@/Lib/Preventive-Date';
import type { AssetHistoryItem } from '@/Types/AssetCards/AssetCard';

interface Props {
    items: AssetHistoryItem[];
}

export function AssetHistory({ items }: Props) {
    if (items.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Este activo aún no tiene incidencias ni mantenimientos
                preventivos.
            </p>
        );
    }

    return (
        <ol className="divide-y rounded-md border">
            {items.map((item) => {
                const Icon =
                    item.type === 'ticket' ? AlertTriangle : CalendarCheck;

                return (
                    <li
                        key={item.key}
                        className="flex flex-wrap items-center gap-3 p-3 text-sm"
                    >
                        <Icon className="size-4 shrink-0 text-muted-foreground" />

                        <div className="min-w-0 flex-1">
                            <Link
                                href={item.url}
                                className="font-medium hover:underline"
                            >
                                {item.title}
                            </Link>

                            <p className="text-xs text-muted-foreground">
                                {item.type === 'ticket'
                                    ? 'Incidencia'
                                    : 'Mantenimiento preventivo'}
                                {' · '}
                                {item.has_time
                                    ? formatPreventiveDateTime(item.occurred_at)
                                    : formatPreventiveDate(item.occurred_at)}
                            </p>
                        </div>

                        <div className="flex flex-wrap gap-1">
                            <TicketStatusBadge
                                label={item.status_label}
                                color={item.status_color}
                            />

                            {item.is_overdue && (
                                <TicketStatusBadge
                                    label="Vencida"
                                    color="danger"
                                />
                            )}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
