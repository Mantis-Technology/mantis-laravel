import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/Ui/Table';
import type { StatisticsTechnician } from '@/Types/Statistics/Statistics';

interface Props {
    data: StatisticsTechnician[];
}

const formatHours = (value: number | null): string => {
    if (value === null) {
        return '—';
    }

    return `${value.toLocaleString('es-CO', {
        maximumFractionDigits: 1,
    })} h`;
};

const formatRate = (value: number | null): string => {
    if (value === null) {
        return '—';
    }

    return `${value.toLocaleString('es-CO', {
        maximumFractionDigits: 1,
    })} %`;
};

export function TechnicianTable({ data }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Comportamiento de los técnicos</CardTitle>

                <CardDescription>
                    Carga de trabajo, resolución y cumplimiento de SLA por
                    técnico.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <div className="overflow-hidden rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Técnico</TableHead>
                                <TableHead className="text-right">
                                    Casos asignados
                                </TableHead>
                                <TableHead className="text-right">
                                    Resueltos
                                </TableHead>
                                <TableHead className="text-right">
                                    Preventivos
                                </TableHead>
                                <TableHead className="text-right">
                                    Tiempo medio
                                </TableHead>
                                <TableHead className="text-right">
                                    Horas trabajadas
                                </TableHead>
                                <TableHead className="text-right">
                                    Cumplimiento SLA
                                </TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={7}
                                        className="h-24 text-center text-muted-foreground"
                                    >
                                        No hay técnicos para mostrar.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                data.map((technician) => (
                                    <TableRow key={technician.id}>
                                        <TableCell className="font-medium">
                                            {technician.name}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {technician.assigned_tickets}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {technician.resolved_tickets}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {technician.completed_preventive}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {formatHours(
                                                technician.average_resolution_hours,
                                            )}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {formatHours(
                                                technician.worked_hours,
                                            )}
                                        </TableCell>

                                        <TableCell className="text-right tabular-nums">
                                            {formatRate(
                                                technician.sla_compliance_rate,
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </CardContent>
        </Card>
    );
}
