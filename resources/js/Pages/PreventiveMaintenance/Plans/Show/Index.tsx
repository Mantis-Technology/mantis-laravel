import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import { formatPreventiveDate } from '@/Lib/Preventive-Date';
import preventivePlansRoutes from '@/routes/preventive-plans';
import type { PlanDetail } from '@/Types/PreventiveMaintenance/Plan';

import { ExecutionList } from './Partials/Execution-List';
import { PlanActions } from './Partials/Plan-Actions';

interface Props {
    [key: string]: unknown;
    plan: PlanDetail;
    can_manage: boolean;
    can_generate: boolean;
}

function Detail({ label, value }: { label: string; value: string }) {
    return (
        <div>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}

export default function PreventivePlansShow({
    plan,
    can_manage,
    can_generate,
}: Props) {
    const isEditable = !['finished', 'cancelled'].includes(plan.status);

    return (
        <div className="w-full py-10">
            <Head title={plan.title} />

            <Link
                href={preventivePlansRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a planes preventivos
            </Link>

            <div className="mb-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {plan.title}
                        </h1>

                        <TicketStatusBadge
                            label={plan.status_label}
                            color={plan.status_color}
                        />
                    </div>

                    <p className="mt-2 text-sm text-muted-foreground">
                        {plan.frequency.label}
                        {plan.asset_card && ` · Activo ${plan.asset_card.code}`}
                    </p>
                </div>

                {can_manage && isEditable && (
                    <Link
                        href={preventivePlansRoutes.edit.url(plan.id)}
                        className="inline-flex h-9 items-center gap-2 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground"
                    >
                        <Pencil className="h-4 w-4" />
                        Editar plan
                    </Link>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Ejecuciones</CardTitle>
                            <CardDescription>
                                Calendario de mantenimientos generados por este
                                plan.
                            </CardDescription>
                        </CardHeader>

                        <CardContent>
                            <ExecutionList executions={plan.executions} />
                        </CardContent>
                    </Card>

                    {(plan.description || plan.instructions) && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Descripción e instrucciones</CardTitle>
                            </CardHeader>

                            <CardContent className="space-y-4 text-sm">
                                {plan.description && (
                                    <div>
                                        <p className="mb-1 font-medium">
                                            Descripción
                                        </p>
                                        <p className="whitespace-pre-line text-muted-foreground">
                                            {plan.description}
                                        </p>
                                    </div>
                                )}

                                {plan.instructions && (
                                    <div>
                                        <p className="mb-1 font-medium">
                                            Instrucciones
                                        </p>
                                        <p className="whitespace-pre-line text-muted-foreground">
                                            {plan.instructions}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles</CardTitle>
                        </CardHeader>

                        <CardContent>
                            <dl className="space-y-3 text-sm">
                                <Detail
                                    label="Categoría"
                                    value={plan.category?.name ?? '—'}
                                />
                                <Detail
                                    label="Responsable"
                                    value={plan.responsible?.name ?? '—'}
                                />
                                <Detail
                                    label="Inicio"
                                    value={formatPreventiveDate(plan.starts_at)}
                                />
                                <Detail
                                    label="Fin"
                                    value={formatPreventiveDate(plan.ends_at)}
                                />
                                <Detail
                                    label="Creado por"
                                    value={plan.created_by?.name ?? '—'}
                                />
                            </dl>
                        </CardContent>
                    </Card>

                    {can_manage && (
                        <Card>
                            <CardHeader>
                                <CardTitle>Acciones</CardTitle>
                            </CardHeader>

                            <CardContent>
                                <PlanActions
                                    planId={plan.id}
                                    transitions={plan.allowed_transitions}
                                    canGenerate={can_generate}
                                />
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
}
