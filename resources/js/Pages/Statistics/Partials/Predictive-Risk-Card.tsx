import {
    BrainCircuitIcon,
    RefreshCwIcon,
    TriangleAlertIcon,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import { cn } from '@/Lib/Utils';
import type {
    PredictiveRisk,
    PredictiveRiskAsset,
} from '@/Types/Statistics/PredictiveRisk';

interface Props {
    initial: PredictiveRisk;
    url: string;
}

const REFRESH_INTERVAL_MS = 60_000;

const RISK_BAR_CLASSES: Record<string, string> = {
    danger: 'bg-destructive',
    warning: 'bg-amber-500',
    success: 'bg-emerald-500',
};

const formatDate = (value: string): string =>
    new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
    }).format(new Date(`${value}T00:00:00`));

const formatDateTime = (value: string): string =>
    new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const formatProbability = (value: number): string =>
    `${Math.round(value * 100).toLocaleString('es-CO')} %`;

function AssetRiskRow({ asset }: { asset: PredictiveRiskAsset }) {
    const width = Math.min(100, Math.max(0, asset.probability * 100));

    return (
        <li className="grid gap-3 py-3 first:pt-0 last:pb-0 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
            <div className="min-w-0">
                <p className="truncate font-medium">{asset.code}</p>

                <p className="truncate text-xs text-muted-foreground">
                    {[asset.type, asset.site].filter(Boolean).join(' · ') ||
                        'Sin clasificación'}
                </p>
            </div>

            <div className="flex items-center gap-3">
                <div
                    className="h-2 w-24 overflow-hidden rounded-full bg-muted"
                    aria-hidden="true"
                >
                    <div
                        className={cn(
                            'h-full rounded-full',
                            RISK_BAR_CLASSES[asset.risk_color] ??
                                RISK_BAR_CLASSES.success,
                        )}
                        style={{ width: `${width}%` }}
                    />
                </div>

                <span className="w-14 text-right text-sm font-medium tabular-nums">
                    {formatProbability(asset.probability)}
                </span>

                <TicketStatusBadge
                    label={asset.risk}
                    color={asset.risk_color}
                    className="w-14 justify-center"
                />
            </div>
        </li>
    );
}

export function PredictiveRiskCard({ initial, url }: Props) {
    const [risk, setRisk] = useState(initial);
    const [refreshing, setRefreshing] = useState(false);
    const [hasError, setHasError] = useState(false);

    useEffect(() => {
        let active = true;

        const refresh = async () => {
            setRefreshing(true);

            try {
                const response = await fetch(url, {
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    throw new Error(
                        `Prediction request failed: ${response.status}`,
                    );
                }

                const next = (await response.json()) as PredictiveRisk;

                if (active) {
                    setRisk(next);
                    setHasError(false);
                }
            } catch {
                if (active) {
                    setHasError(true);
                }
            } finally {
                if (active) {
                    setRefreshing(false);
                }
            }
        };

        const interval = window.setInterval(refresh, REFRESH_INTERVAL_MS);

        return () => {
            active = false;
            window.clearInterval(interval);
        };
    }, [url]);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <BrainCircuitIcon className="size-4 text-primary" />
                    Riesgo de falla en los próximos 30 días
                    <span className="ml-auto inline-flex items-center gap-1 text-xs font-normal text-muted-foreground">
                        {refreshing && (
                            <RefreshCwIcon className="size-3 animate-spin" />
                        )}
                        Actualización cada minuto
                    </span>
                </CardTitle>

                <CardDescription>
                    Predicción generada con el modelo exportado{' '}
                    <span className="font-medium text-foreground">
                        {risk.model}
                    </span>
                    . Se recalcula cuando hay datos nuevos.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <div className="mb-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div className="rounded-lg bg-muted/60 px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            Activos evaluados
                        </p>
                        <p className="text-lg font-semibold tabular-nums">
                            {risk.summary.assets.toLocaleString('es-CO')}
                        </p>
                    </div>

                    <div className="rounded-lg bg-destructive/10 px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            Riesgo alto
                        </p>
                        <p className="text-lg font-semibold text-destructive tabular-nums">
                            {risk.summary.high.toLocaleString('es-CO')}
                        </p>
                    </div>

                    <div className="rounded-lg bg-amber-500/10 px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            Riesgo medio
                        </p>
                        <p className="text-lg font-semibold text-amber-700 tabular-nums dark:text-amber-300">
                            {risk.summary.medium.toLocaleString('es-CO')}
                        </p>
                    </div>

                    <div className="rounded-lg bg-muted/60 px-3 py-2">
                        <p className="text-xs text-muted-foreground">
                            Probabilidad media
                        </p>
                        <p className="text-lg font-semibold tabular-nums">
                            {formatProbability(
                                risk.summary.average_probability,
                            )}
                        </p>
                    </div>
                </div>

                {risk.assets.length === 0 ? (
                    <div className="flex flex-col items-center gap-2 py-10 text-center">
                        <TriangleAlertIcon className="size-6 text-muted-foreground" />

                        <p className="text-sm text-muted-foreground">
                            Todavía no hay activos para evaluar.
                        </p>
                    </div>
                ) : (
                    <>
                        <p className="mb-2 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Activos con mayor riesgo
                        </p>

                        <ul className="divide-y">
                            {risk.assets.map((asset) => (
                                <AssetRiskRow
                                    key={asset.asset_id}
                                    asset={asset}
                                />
                            ))}
                        </ul>
                    </>
                )}

                <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
                    <span>
                        Corte: {formatDate(risk.cutoff)} · Ventana:{' '}
                        {risk.window_days} días
                    </span>

                    <span>
                        Actualizado: {formatDateTime(risk.generated_at)}
                        {hasError ? ' · No se pudo actualizar' : ''}
                    </span>
                </div>
            </CardContent>
        </Card>
    );
}
