import { Link } from '@inertiajs/react';
import { AlertTriangleIcon, ShieldAlertIcon } from 'lucide-react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import {
    formatPreventiveDate,
    formatPreventiveDateTime,
} from '@/Lib/Preventive-Date';
import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import ticketsRoutes from '@/routes/tickets';
import type { StatisticsAttention } from '@/Types/Statistics/Statistics';

interface Props {
    attention: StatisticsAttention;
}

function EmptyState({ message }: { message: string }) {
    return (
        <p className="py-10 text-center text-sm text-muted-foreground">
            {message}
        </p>
    );
}

export function AttentionCards({ attention }: Props) {
    return (
        <div className="grid gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <AlertTriangleIcon className="size-4 text-amber-600" />
                        Preventivos vencidos
                    </CardTitle>

                    <CardDescription>
                        Ejecuciones programadas antes de hoy que aún no se han
                        completado.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {attention.overdue_preventive.length === 0 ? (
                        <EmptyState message="No hay preventivos vencidos." />
                    ) : (
                        <ul className="divide-y">
                            {attention.overdue_preventive.map((execution) => (
                                <li
                                    key={execution.id}
                                    className="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <Link
                                            href={preventiveExecutionsRoutes.show.url(
                                                execution.id,
                                            )}
                                            className="block truncate font-medium hover:underline"
                                        >
                                            {execution.title}
                                        </Link>

                                        <p className="text-xs text-muted-foreground">
                                            {execution.asset_card?.code ??
                                                'Sin activo'}{' '}
                                            ·{' '}
                                            {formatPreventiveDate(
                                                execution.scheduled_at,
                                            )}
                                            {execution.assignee
                                                ? ` · ${execution.assignee}`
                                                : ''}
                                        </p>
                                    </div>

                                    <TicketStatusBadge
                                        label={`${execution.days_overdue} días`}
                                        color="danger"
                                    />
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <ShieldAlertIcon className="size-4 text-destructive" />
                        Casos con SLA comprometido
                    </CardTitle>

                    <CardDescription>
                        Casos abiertos cuyo tiempo de resolución está por vencer
                        o ya venció.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {attention.sla_tickets.length === 0 ? (
                        <EmptyState message="No hay casos con SLA comprometido." />
                    ) : (
                        <ul className="divide-y">
                            {attention.sla_tickets.map((ticket) => (
                                <li
                                    key={ticket.id}
                                    className="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                                >
                                    <div className="min-w-0 space-y-1">
                                        <Link
                                            href={ticketsRoutes.show.url(
                                                ticket.id,
                                            )}
                                            className="block truncate font-medium hover:underline"
                                        >
                                            {ticket.title}
                                        </Link>

                                        <p className="text-xs text-muted-foreground">
                                            {ticket.asset_card?.code ??
                                                'Sin activo'}{' '}
                                            · Vence{' '}
                                            {formatPreventiveDateTime(
                                                ticket.due_at,
                                            )}
                                            {ticket.assignee
                                                ? ` · ${ticket.assignee}`
                                                : ''}
                                        </p>
                                    </div>

                                    <div className="flex shrink-0 flex-wrap justify-end gap-1">
                                        <TicketStatusBadge
                                            label={ticket.status_label}
                                            color={ticket.status_color}
                                        />

                                        <TicketStatusBadge
                                            label={ticket.sla_state_label}
                                            color={ticket.sla_state_color}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
