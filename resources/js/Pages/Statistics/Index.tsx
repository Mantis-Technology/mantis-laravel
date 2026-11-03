import { Head } from '@inertiajs/react';

import { AttentionCards } from '@/Pages/Statistics/Partials/Attention-Cards';
import { CategoryCard } from '@/Pages/Statistics/Partials/Category-Card';
import { PredictiveRiskCard } from '@/Pages/Statistics/Partials/Predictive-Risk-Card';
import { StatisticsFiltersPopover } from '@/Pages/Statistics/Partials/Statistics-Filters';
import { StatusCard } from '@/Pages/Statistics/Partials/Status-Card';
import { SummaryCards } from '@/Pages/Statistics/Partials/Summary-Cards';
import { TechnicianTable } from '@/Pages/Statistics/Partials/Technician-Table';
import { TrendCard } from '@/Pages/Statistics/Partials/Trend-Card';
import type { StatisticsPageProps } from '@/Types/Statistics/Statistics';

const formatGeneratedAt = (value: string): string =>
    new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

export default function StatisticsIndex({
    summary,
    trend,
    ticket_status_distribution,
    category_distribution,
    technicians,
    attention,
    filters,
    options,
    predictive_risk,
    predictive_risk_url,
    generated_at,
}: StatisticsPageProps) {
    return (
        <>
            <Head title="Estadísticas" />

            <div className="w-full space-y-6 py-10">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Estadísticas operativas
                        </h1>

                        <p className="mt-2 text-sm text-muted-foreground">
                            Consulta la carga de casos, los tiempos de atención,
                            las horas trabajadas, la distribución por categorías
                            y el comportamiento de los técnicos.
                        </p>
                    </div>

                    <StatisticsFiltersPopover
                        filters={filters}
                        options={options}
                    />
                </div>

                <SummaryCards summary={summary} />

                <PredictiveRiskCard
                    key={predictive_risk.generated_at}
                    initial={predictive_risk}
                    url={predictive_risk_url}
                />

                <div className="grid gap-6 xl:grid-cols-3">
                    <TrendCard data={trend} />

                    <StatusCard data={ticket_status_distribution} />
                </div>

                <CategoryCard data={category_distribution} />

                <TechnicianTable data={technicians} />

                <AttentionCards attention={attention} />

                <p className="text-right text-xs text-muted-foreground">
                    Información calculada el {formatGeneratedAt(generated_at)}.
                </p>
            </div>
        </>
    );
}
