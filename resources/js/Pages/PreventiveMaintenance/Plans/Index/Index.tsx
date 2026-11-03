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
import { formatPreventiveDate } from '@/Lib/Preventive-Date';
import preventivePlansRoutes from '@/routes/preventive-plans';
import type { PreventiveStatusOption } from '@/Types/PreventiveMaintenance/Common';
import type { PlanSummary } from '@/Types/PreventiveMaintenance/Plan';

interface Props {
    [key: string]: unknown;
    plans: PlanSummary[];
    filters: { status: string | null };
    statuses: PreventiveStatusOption[];
    can_manage: boolean;
}

export default function PreventivePlansIndex({
    plans,
    filters,
    statuses,
    can_manage,
}: Props) {
    const changeStatus = (event: ChangeEvent<HTMLSelectElement>) => {
        const value = event.target.value;

        router.get(
            preventivePlansRoutes.index.url(),
            value === '' ? {} : { status: value },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <div className="w-full py-10">
            <Head title="Planes preventivos" />

            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Planes preventivos
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Mantenimientos programados por tiempo para tus activos.
                    </p>
                </div>

                {can_manage && (
                    <Link
                        href={preventivePlansRoutes.create.url()}
                        className="inline-flex h-9 items-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                    >
                        <Plus className="h-4 w-4" />
                        Nuevo plan
                    </Link>
                )}
            </div>

            <div className="mb-4">
                <select
                    value={filters.status ?? ''}
                    onChange={changeStatus}
                    aria-label="Filtrar por estado"
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
                            <TableHead>Plan</TableHead>
                            <TableHead>Activo</TableHead>
                            <TableHead>Categoría</TableHead>
                            <TableHead>Frecuencia</TableHead>
                            <TableHead>Responsable</TableHead>
                            <TableHead>Inicio</TableHead>
                            <TableHead>Fin</TableHead>
                            <TableHead>Estado</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {plans.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={8}
                                    className="py-10 text-center text-sm text-muted-foreground"
                                >
                                    No hay planes preventivos para mostrar.
                                </TableCell>
                            </TableRow>
                        )}

                        {plans.map((plan) => (
                            <TableRow key={plan.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={preventivePlansRoutes.show.url(
                                            plan.id,
                                        )}
                                        className="hover:underline"
                                    >
                                        {plan.title}
                                    </Link>
                                </TableCell>
                                <TableCell>
                                    {plan.asset_card?.code ?? '—'}
                                </TableCell>
                                <TableCell>
                                    {plan.category?.name ?? '—'}
                                </TableCell>
                                <TableCell>{plan.frequency.label}</TableCell>
                                <TableCell>
                                    {plan.responsible?.name ?? '—'}
                                </TableCell>
                                <TableCell>
                                    {formatPreventiveDate(plan.starts_at)}
                                </TableCell>
                                <TableCell>
                                    {formatPreventiveDate(plan.ends_at)}
                                </TableCell>
                                <TableCell>
                                    <TicketStatusBadge
                                        label={plan.status_label}
                                        color={plan.status_color}
                                    />
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
