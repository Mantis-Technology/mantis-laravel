import { Link } from '@inertiajs/react';

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
import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import type { PlanExecutionItem } from '@/Types/PreventiveMaintenance/Plan';

interface Props {
    executions: PlanExecutionItem[];
}

export function ExecutionList({ executions }: Props) {
    return (
        <div className="overflow-hidden rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Fecha</TableHead>
                        <TableHead>Ejecución</TableHead>
                        <TableHead>Responsable</TableHead>
                        <TableHead>Estado</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    {executions.length === 0 && (
                        <TableRow>
                            <TableCell
                                colSpan={4}
                                className="py-8 text-center text-sm text-muted-foreground"
                            >
                                Este plan aún no tiene ejecuciones.
                            </TableCell>
                        </TableRow>
                    )}

                    {executions.map((execution) => (
                        <TableRow key={execution.id}>
                            <TableCell>
                                {formatPreventiveDate(execution.scheduled_at)}
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
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
