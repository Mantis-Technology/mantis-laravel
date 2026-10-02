import { Link, router } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import type { ChangeEvent } from 'react';

import { TicketStatusBadge } from '@/components/tickets/ticket-status-badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import ticketsRoutes from '@/routes/tickets';
import type {
    TicketListItem,
    TicketStatusOption,
} from '@/types/tickets/ticket';

interface Props {
    [key: string]: unknown;
    tickets: TicketListItem[];
    filters: {
        status: string | null;
    };
    statuses: TicketStatusOption[];
}

const formatDate = (date: string | null) => {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

export default function TicketsIndex({ tickets, filters, statuses }: Props) {
    const handleStatusChange = (event: ChangeEvent<HTMLSelectElement>) => {
        const status = event.target.value;

        router.get(ticketsRoutes.index.url(), status === '' ? {} : { status }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="w-full py-10">
            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Casos de mantenimiento
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Consulta el estado de las incidencias y su avance en el
                        proceso de mantenimiento.
                    </p>
                </div>

                <Link
                    href={ticketsRoutes.create.url()}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <AlertTriangle className="h-4 w-4" />
                    Reportar incidencia
                </Link>
            </div>

            <div className="mb-4 flex items-center gap-2">
                <label
                    htmlFor="status"
                    className="text-sm font-medium text-muted-foreground"
                >
                    Estado
                </label>

                <select
                    id="status"
                    value={filters.status ?? ''}
                    onChange={handleStatusChange}
                    className="flex h-9 w-full max-w-56 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <option value="">Todos los estados</option>

                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>
            </div>

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Activo</TableHead>
                            <TableHead>Caso</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead>Reportado por</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead>Creado</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {tickets.length ? (
                            tickets.map((ticket) => (
                                <TableRow key={ticket.id}>
                                    <TableCell className="font-medium">
                                        {ticket.asset_card?.code ?? '—'}
                                    </TableCell>

                                    <TableCell>
                                        <Link
                                            href={ticketsRoutes.show.url(
                                                ticket.id,
                                            )}
                                            className="font-medium hover:underline"
                                        >
                                            {ticket.title}
                                        </Link>
                                    </TableCell>

                                    <TableCell>
                                        <TicketStatusBadge
                                            label={ticket.status_label}
                                            color={ticket.status_color}
                                        />
                                    </TableCell>

                                    <TableCell className="text-muted-foreground">
                                        {ticket.reporter?.name ?? '—'}
                                    </TableCell>

                                    <TableCell>
                                        {ticket.assignee ? (
                                            <span className="text-muted-foreground">
                                                {ticket.assignee.name}
                                            </span>
                                        ) : (
                                            <span className="text-amber-600 dark:text-amber-400">
                                                Sin asignar
                                            </span>
                                        )}
                                    </TableCell>

                                    <TableCell className="text-muted-foreground">
                                        {formatDate(ticket.created_at)}
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="h-24 text-center"
                                >
                                    No hay casos de mantenimiento.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
