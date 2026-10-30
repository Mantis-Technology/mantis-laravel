import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';

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
import preventivePlansRoutes from '@/routes/preventive-plans';
import type { PreventiveUser } from '@/Types/PreventiveMaintenance/Common';
import type { ExecutionDetail } from '@/Types/PreventiveMaintenance/Execution';

import { PostponeForm } from './Partials/Postpone-Form';
import { ReassignForm } from './Partials/Reassign-Form';
import { StatusActions } from './Partials/Status-Actions';
import { StatusTimeline } from './Partials/Status-Timeline';

interface Props {
    [key: string]: unknown;
    execution: ExecutionDetail;
    technicians: PreventiveUser[];
}

function InfoRow({ label, children }: { label: string; children: ReactNode }) {
    return (
        <div className="space-y-1">
            <dt className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {label}
            </dt>

            <dd>{children}</dd>
        </div>
    );
}

export default function PreventiveExecutionsShow({
    execution,
    technicians,
}: Props) {
    return (
        <div className="w-full py-10">
            <Head title={execution.title} />

            <Link
                href={preventiveExecutionsRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a ejecuciones
            </Link>

            <div className="mb-8">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Ejecución #{execution.id} ·{' '}
                    {execution.asset_card?.code ?? 'Activo'}
                </p>

                <div className="mt-1 flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {execution.title}
                    </h1>

                    <TicketStatusBadge
                        label={execution.status_label}
                        color={execution.status_color}
                    />

                    {execution.is_overdue && (
                        <TicketStatusBadge label="Vencida" color="danger" />
                    )}

                    {execution.is_postponed && (
                        <TicketStatusBadge label="Aplazada" color="warning" />
                    )}
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalle del mantenimiento</CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            <p className="text-sm whitespace-pre-line text-muted-foreground">
                                {execution.description ?? 'Sin descripción.'}
                            </p>

                            {execution.instructions && (
                                <div className="space-y-1 text-sm">
                                    <p className="font-medium">Instrucciones</p>
                                    <p className="whitespace-pre-line text-muted-foreground">
                                        {execution.instructions}
                                    </p>
                                </div>
                            )}

                            <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                <InfoRow label="Activo">
                                    {execution.asset_card?.code ?? '—'}
                                </InfoRow>

                                <InfoRow label="Categoría">
                                    {execution.category?.name ?? '—'}
                                </InfoRow>

                                <InfoRow label="Fecha programada">
                                    {formatPreventiveDate(
                                        execution.scheduled_at,
                                    )}
                                </InfoRow>

                                {execution.is_postponed && (
                                    <InfoRow label="Fecha original">
                                        {formatPreventiveDate(
                                            execution.original_scheduled_at,
                                        )}
                                    </InfoRow>
                                )}

                                <InfoRow label="Plan">
                                    {execution.plan ? (
                                        <Link
                                            href={preventivePlansRoutes.show.url(
                                                execution.plan.id,
                                            )}
                                            className="hover:underline"
                                        >
                                            {execution.plan.title}
                                        </Link>
                                    ) : (
                                        'Mantenimiento puntual'
                                    )}
                                </InfoRow>

                                <InfoRow label="Responsable">
                                    {execution.assignee?.name ?? 'Sin asignar'}
                                </InfoRow>
                            </dl>
                        </CardContent>
                    </Card>

                    {(execution.execution_notes || execution.findings) && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Resultado</CardTitle>

                                <CardDescription>
                                    Completada el{' '}
                                    {formatPreventiveDateTime(
                                        execution.completed_at,
                                    )}
                                    {execution.completed_by &&
                                        ` por ${execution.completed_by.name}`}
                                    .
                                </CardDescription>
                            </CardHeader>

                            <CardContent className="space-y-4 text-sm">
                                {execution.execution_notes && (
                                    <div>
                                        <p className="mb-1 font-medium">
                                            Notas de ejecución
                                        </p>
                                        <p className="whitespace-pre-line text-muted-foreground">
                                            {execution.execution_notes}
                                        </p>
                                    </div>
                                )}

                                {execution.findings && (
                                    <div>
                                        <p className="mb-1 font-medium">
                                            Hallazgos
                                        </p>
                                        <p className="whitespace-pre-line text-muted-foreground">
                                            {execution.findings}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de estados</CardTitle>

                            <CardDescription>
                                Seguimiento del ciclo de vida de la ejecución.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <StatusTimeline
                                transitions={execution.status_transitions}
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Gestión de la ejecución</CardTitle>

                            <CardDescription>
                                Avanza la ejecución según su etapa.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <StatusActions
                                executionId={execution.id}
                                transitions={execution.allowed_transitions}
                                isFinal={execution.is_final}
                            />
                        </CardContent>
                    </Card>

                    {execution.can_postpone && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Aplazar</CardTitle>

                                <CardDescription>
                                    Mueve la ejecución a una fecha posterior. El
                                    plan no volverá a generar la fecha original.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <PostponeForm executionId={execution.id} />
                            </CardContent>
                        </Card>
                    )}

                    {execution.can_reassign && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Reasignar técnico</CardTitle>

                                <CardDescription>
                                    Cambia el responsable de esta ejecución.
                                </CardDescription>
                            </CardHeader>

                            <CardContent>
                                <ReassignForm
                                    executionId={execution.id}
                                    technicians={technicians}
                                    currentTechnicianId={
                                        execution.assignee?.id ?? null
                                    }
                                />
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
}
