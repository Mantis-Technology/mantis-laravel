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
import type { TicketDetail, TicketUser } from '@/types/tickets/ticket';

import { StatusActions } from './partials/status-actions';
import { StatusTimeline } from './partials/status-timeline';

interface Props {
    ticket: TicketDetail;
    technicians: TicketUser[];
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

export default function TicketsShow({ ticket, technicians }: Props) {
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
                                isFinal={ticket.is_final}
                            />
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
