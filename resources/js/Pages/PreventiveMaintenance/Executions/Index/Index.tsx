import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ChangeEvent } from 'react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Ui/Table';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import { formatPreventiveDate } from '@/Lib/Preventive-Date';
import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import type { PreventiveStatusOption } from '@/Types/PreventiveMaintenance/Common';
import type { ExecutionSummary } from '@/Types/PreventiveMaintenance/Execution';

interface Props {
    [key: string]: unknown;
    executions: ExecutionSummary[];
    filters: { status: string | null; assigned: string | null };
    statuses: PreventiveStatusOption[];
    can_manage: boolean;
}

export default function PreventiveExecutionsIndex({
    executions,
    filters,
    statuses,
    can_manage,
}: Props) {
    const applyFilters = (status: string, assigned: boolean) => {
        const params: Record<string, string> = {};

        if (status !== '') {
            params.status = status;
        }

        if (assigned) {
            params.assigned = 'me';
        }

        router.get(preventiveExecutionsRoutes.index.url(), params, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <div className="w-full py-10">
            <Head title="Ejecuciones preventivas" />

            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Ejecuciones preventivas
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Agenda de mantenimientos preventivos programados.
                    </p>
                </div>

                {can_manage && (
                    <Link
                        href={preventiveExecutionsRoutes.create.url()}
                        className="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                    >
                        <Plus className="h-4 w-4" />
                        Programar mantenimiento
                    </Link>
                )}
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-4">
                <select
                    value={filters.status ?? ''}
                    onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                        applyFilters(
                            event.target.value,
                            filters.assigned === 'me',
                        )
                    }
                    aria-label="Filtrar por estado"
                    className={`${SELECT_CLASS} max-w-56`}
                >
                    <option value="">Todos los estados</option>

                    {statuses.map((status) => (
                        <option key={status.value} value={status.value}>
                            {status.label}
                        </option>
                    ))}
                </select>

                <label className="inline-flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={filters.assigned === 'me'}
                        onChange={(event) =>
                            applyFilters(
                                filters.status ?? '',
                                event.target.checked,
                            )
                        }
                    />
                    Solo mis ejecuciones
                </label>
            </div>

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Fecha</TableHead>
                            <TableHead>Ejecución</TableHead>
                            <TableHead>Activo</TableHead>
                            <TableHead>Plan</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead>Estado</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {executions.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="py-10 text-center text-sm text-muted-foreground"
                                >
                                    No hay ejecuciones para mostrar.
                                </TableCell>
                            </TableRow>
                        )}

                        {executions.map((execution) => (
                            <TableRow key={execution.id}>
                                <TableCell>
                                    {formatPreventiveDate(
                                        execution.scheduled_at,
                                    )}
                                </TableCell>

                                <TableCell className="font-medium">
                                    <Link
                                        href={preventiveExecutionsRoutes.show.url(
                                            execution.id,
                                        )}
                                        className="hover:underline"
                                    >
                                        {execution.title}
                                    </Link>
                                </TableCell>

                                <TableCell>
                                    {execution.asset_card?.code ?? '—'}
                                </TableCell>

                                <TableCell>
                                    {execution.plan?.title ?? 'Puntual'}
                                </TableCell>

                                <TableCell>
                                    {execution.assignee?.name ?? '—'}
                                </TableCell>

                                <TableCell>
                                    <div className="flex flex-wrap gap-1">
                                        <TicketStatusBadge
                                            label={execution.status_label}
                                            color={execution.status_color}
                                        />

                                        {execution.is_overdue && (
                                            <TicketStatusBadge
                                                label="Vencida"
                                                color="danger"
                                            />
                                        )}

                                        {execution.is_postponed && (
                                            <TicketStatusBadge
                                                label="Aplazada"
                                                color="warning"
                                            />
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
