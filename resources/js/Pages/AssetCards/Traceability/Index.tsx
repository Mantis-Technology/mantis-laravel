import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, CalendarCheck } from 'lucide-react';
import type { ChangeEvent } from 'react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import { formatPreventiveDateTime } from '@/Lib/Preventive-Date';
import type {
    TraceabilityEvent,
    TraceabilityFilters,
    TraceabilityPagination,
    TraceabilityUser,
} from '@/Types/AssetCards/Traceability';

interface Props {
    assetCard: { id: number; code: string };
    events: TraceabilityEvent[];
    filters: TraceabilityFilters;
    users: TraceabilityUser[];
    pagination: TraceabilityPagination;
    baseUrl: string;
    showUrl: string;
}

export default function AssetTraceability({
    assetCard,
    events,
    filters,
    users,
    pagination,
    baseUrl,
    showUrl,
}: Props) {
    const source = filters.source ?? '';
    const user = filters.user === null ? '' : String(filters.user);

    const applyFilters = (next: { source: string; user: string }) => {
        const query: Record<string, string> = {};

        if (next.source !== '') {
            query.source = next.source;
        }

        if (next.user !== '') {
            query.user = next.user;
        }

        router.get(baseUrl, query, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <div className="w-full py-10">
            <Head title={`Trazabilidad ${assetCard.code}`} />

            <Link
                href={showUrl}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver al activo
            </Link>

            <div className="mb-8">
                <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                    Activo {assetCard.code}
                </p>

                <h1 className="mt-1 text-2xl font-semibold tracking-tight">
                    Trazabilidad del activo
                </h1>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Quién hizo qué y cuándo</CardTitle>

                    <CardDescription>
                        Eventos de las incidencias y los mantenimientos
                        preventivos de este activo, del más reciente al más
                        antiguo.
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-4">
                    <div className="grid gap-3 sm:grid-cols-2">
                        <select
                            aria-label="Filtrar por origen"
                            value={source}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                applyFilters({
                                    source: event.target.value,
                                    user,
                                })
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los orígenes</option>
                            <option value="ticket">Incidencias</option>
                            <option value="preventive">
                                Mantenimientos preventivos
                            </option>
                        </select>

                        <select
                            aria-label="Filtrar por usuario"
                            value={user}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                applyFilters({
                                    source,
                                    user: event.target.value,
                                })
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los usuarios</option>

                            {users.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    {events.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay eventos para mostrar con estos filtros.
                        </p>
                    ) : (
                        <ol className="divide-y rounded-md border">
                            {events.map((event) => {
                                const Icon =
                                    event.source === 'ticket'
                                        ? AlertTriangle
                                        : CalendarCheck;

                                return (
                                    <li
                                        key={event.key}
                                        className="space-y-1 p-3 text-sm"
                                    >
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Icon className="size-4 shrink-0 text-muted-foreground" />

                                            <TicketStatusBadge
                                                label={event.action}
                                                color={
                                                    event.source === 'ticket'
                                                        ? 'info'
                                                        : 'gray'
                                                }
                                            />

                                            <Link
                                                href={event.url}
                                                className="font-medium hover:underline"
                                            >
                                                {event.title}
                                            </Link>
                                        </div>

                                        {event.detail && <p>{event.detail}</p>}

                                        {event.note && (
                                            <p className="text-muted-foreground">
                                                {event.note}
                                            </p>
                                        )}

                                        <p className="text-xs text-muted-foreground">
                                            {event.source === 'ticket'
                                                ? 'Incidencia'
                                                : 'Mantenimiento preventivo'}
                                            {' · '}
                                            {event.user?.name ?? 'Sistema'}
                                            {' · '}
                                            {formatPreventiveDateTime(
                                                event.occurred_at,
                                            )}
                                        </p>
                                    </li>
                                );
                            })}
                        </ol>
                    )}

                    <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                        <span>
                            Página {pagination.current_page} de{' '}
                            {pagination.last_page} · {pagination.total}{' '}
                            eventos
                        </span>

                        <div className="flex gap-3">
                            {pagination.prev_url && (
                                <Link
                                    href={pagination.prev_url}
                                    preserveScroll
                                    className="font-medium text-foreground hover:underline"
                                >
                                    Anterior
                                </Link>
                            )}

                            {pagination.next_url && (
                                <Link
                                    href={pagination.next_url}
                                    preserveScroll
                                    className="font-medium text-foreground hover:underline"
                                >
                                    Siguiente
                                </Link>
                            )}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
