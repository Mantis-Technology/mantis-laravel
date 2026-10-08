import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';

import { TicketStatusBadge } from '@/components/tickets/ticket-status-badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import ticketsRoutes from '@/routes/tickets';
import type {
    TicketDetail,
    TicketMaintenanceCategoryOption,
    TicketSlaTarget,
    TicketStatusOption,
    TicketUser,
} from '@/types/tickets/ticket';

import { StatusActions } from './partials/status-actions';
import { StatusTimeline } from './partials/status-timeline';

interface Props {
    ticket: TicketDetail;
    technicians: TicketUser[];
    maintenance_categories: TicketMaintenanceCategoryOption[];
    maintenance_types: TicketStatusOption[];
    priorities: TicketStatusOption[];
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

const formatHours = (hours: number) => {
    const sign = hours < 0 ? '-' : '';
    const absolute = Math.abs(hours);

    if (absolute < 24) {
        return `${sign}${absolute} h`;
    }

    const days = Math.floor(absolute / 24);
    const remaining = Math.round(absolute % 24);

    return remaining === 0
        ? `${sign}${days} d`
        : `${sign}${days} d ${remaining} h`;
};

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

function SlaRow({ label, target }: { label: string; target: TicketSlaTarget }) {
    return (
        <div className="space-y-2 rounded-md border p-3">
            <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-medium">{label}</span>

                <TicketStatusBadge
                    label={target.state_label}
                    color={target.state_color}
                />
            </div>

            <dl className="grid grid-cols-2 gap-2 text-xs text-muted-foreground">
                <div>
                    <dt>Objetivo</dt>
                    <dd className="text-foreground">
                        {formatHours(target.target_hours)}
                    </dd>
                </div>

                <div>
                    <dt>Vence</dt>
                    <dd className="text-foreground">
                        {formatDate(target.due_at)}
                    </dd>
                </div>

                <div>
                    <dt>Transcurrido</dt>
                    <dd className="text-foreground">
                        {formatHours(target.elapsed_hours)}
                    </dd>
                </div>

                <div>
                    <dt>{target.happened_at ? 'Resuelto en' : 'Restante'}</dt>
                    <dd className="text-foreground">
                        {target.happened_at
                            ? formatDate(target.happened_at)
                            : formatHours(target.remaining_hours)}
                    </dd>
                </div>
            </dl>
        </div>
    );
}

export default function TicketsShow({
    ticket,
    technicians,
    maintenance_categories,
    maintenance_types,
    priorities,
}: Props) {
    const hasClassification =
        ticket.category !== null &&
        ticket.maintenance_type !== null &&
        ticket.priority !== null;

    return (
        <div className="w-full py-10">
            <Head title={`Caso #${ticket.id}`} />

            <Link
                href={ticketsRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a casos
            </Link>

            <div className="mb-8">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Caso #{ticket.id} · {ticket.asset_card?.code ?? 'Activo'}
                </p>

                <div className="mt-1 flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {ticket.title}
                    </h1>

                    <TicketStatusBadge
                        label={ticket.status_label}
                        color={ticket.status_color}
                    />
                </div>
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalle del caso</CardTitle>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            <p className="text-sm whitespace-pre-line text-muted-foreground">
                                {ticket.description ?? 'Sin descripción.'}
                            </p>

                            <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                <InfoRow label="Activo">
                                    {ticket.asset_card?.code ?? '—'}
                                </InfoRow>

                                <InfoRow label="Ubicación">
                                    {ticket.location?.name ?? 'Sin ubicación'}
                                </InfoRow>

                                <InfoRow label="Reportado por">
                                    {ticket.reporter?.name ?? '—'}
                                </InfoRow>

                                <InfoRow label="Fecha de reporte">
                                    {formatDate(ticket.created_at)}
                                </InfoRow>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Clasificación</CardTitle>

                            <CardDescription>
                                Categoría, tipo de mantenimiento y nivel de
                                atención del caso.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-6">
                            {hasClassification ? (
                                <dl className="grid gap-4 text-sm sm:grid-cols-2">
                                    <InfoRow label="Categoría">
                                        {ticket.category?.name ?? '—'}
                                    </InfoRow>

                                    <InfoRow label="Tipo de mantenimiento">
                                        {ticket.maintenance_type && (
                                            <TicketStatusBadge
                                                label={
                                                    ticket.maintenance_type
                                                        .label
                                                }
                                                color={
                                                    ticket.maintenance_type
                                                        .color
                                                }
                                            />
                                        )}
                                    </InfoRow>

                                    <InfoRow label="Prioridad">
                                        {ticket.priority && (
                                            <TicketStatusBadge
                                                label={ticket.priority.label}
                                                color={ticket.priority.color}
                                            />
                                        )}
                                    </InfoRow>

                                    <InfoRow label="Clasificado por">
                                        {ticket.categorized_by?.name ?? '—'}
                                    </InfoRow>

                                    <InfoRow label="Fecha de clasificación">
                                        {formatDate(ticket.categorized_at)}
                                    </InfoRow>
                                </dl>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    El caso aún no ha sido clasificado. Usa la
                                    acción «Categorizar» para asignar su
                                    categoría, tipo y nivel de atención.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de estados</CardTitle>

                            <CardDescription>
                                Seguimiento del ciclo de vida del caso.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <StatusTimeline
                                transitions={ticket.status_transitions}
                            />
                        </CardContent>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Gestión del caso</CardTitle>

                            <CardDescription>
                                Avanza el caso según la etapa del proceso.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <StatusActions
                                ticketId={ticket.id}
                                transitions={ticket.allowed_transitions}
                                technicians={technicians}
                                maintenanceCategories={maintenance_categories}
                                maintenanceTypes={maintenance_types}
                                priorities={priorities}
                                isFinal={ticket.is_final}
                            />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Nivel de servicio</CardTitle>

                            <CardDescription>
                                Tiempo esperado para la atención del caso.
                            </CardDescription>
                        </CardHeader>

                        <CardContent className="space-y-3">
                            {ticket.sla.has_service_level &&
                            ticket.sla.response &&
                            ticket.sla.resolution ? (
                                <>
                                    <SlaRow
                                        label="Tiempo de respuesta"
                                        target={ticket.sla.response}
                                    />

                                    <SlaRow
                                        label="Tiempo de resolución"
                                        target={ticket.sla.resolution}
                                    />
                                </>
                            ) : (
                                <p className="text-sm text-muted-foreground">
                                    No hay un nivel de servicio configurado para
                                    la clasificación de este caso.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen</CardTitle>
                        </CardHeader>

                        <CardContent>
                            <dl className="grid gap-4 text-sm">
                                <InfoRow label="Estado">
                                    <TicketStatusBadge
                                        label={ticket.status_label}
                                        color={ticket.status_color}
                                    />
                                </InfoRow>

                                <InfoRow label="Responsable">
                                    {ticket.assignee?.name ?? 'Sin asignar'}
                                </InfoRow>

                                <InfoRow label="Última actualización">
                                    {formatDate(ticket.updated_at)}
                                </InfoRow>
                            </dl>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
