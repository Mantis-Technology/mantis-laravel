import { Head, usePage } from '@inertiajs/react';

import { AttentionCards } from '@/Pages/Statistics/Partials/Attention-Cards';
import { CategoryCard } from '@/Pages/Statistics/Partials/Category-Card';
import { PredictiveRiskCard } from '@/Pages/Statistics/Partials/Predictive-Risk-Card';
import { StatisticsFiltersPopover } from '@/Pages/Statistics/Partials/Statistics-Filters';
import { StatusCard } from '@/Pages/Statistics/Partials/Status-Card';
import { SummaryCards } from '@/Pages/Statistics/Partials/Summary-Cards';
import { TechnicianTable } from '@/Pages/Statistics/Partials/Technician-Table';
import { TrendCard } from '@/Pages/Statistics/Partials/Trend-Card';
import type {
    StatisticsPageProps,
    StatisticsSummary,
} from '@/Types/Statistics/Statistics';

const formatGeneratedAt = (value: string): string =>
    new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));

const greeting = (): string => {
    const hour = new Date().getHours();

    if (hour < 12) {
        return 'Buen día';
    }

    return hour < 19 ? 'Buenas tardes' : 'Buenas noches';
};

const plural = (value: number, one: string, many: string): string =>
    `${value.toLocaleString('es-CO')} ${value === 1 ? one : many}`;

function WelcomeBanner({ summary }: { summary: StatisticsSummary }) {
    const { auth } = usePage<{ auth?: { user?: { name?: string } | null } }>()
        .props;
    const firstName = auth?.user?.name?.trim().split(/\s+/)[0] ?? '';
    const attention = summary.sla_overdue_tickets + summary.sla_at_risk_tickets;
    const overdue = summary.overdue_preventive;
    const message =
        attention + overdue === 0
            ? 'Todo al día: no hay casos ni preventivos que requieran atención.'
            : `Tienes ${plural(attention, 'caso que requiere atención', 'casos que requieren atención')} y ${plural(overdue, 'preventivo vencido', 'preventivos vencidos')}.`;

    return (
        <section className="relative overflow-hidden rounded-2xl bg-linear-to-r from-[#B85C43] via-[#9A4630] to-[#2F7A82] p-6 text-white shadow-sm sm:p-8">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -top-16 -right-10 size-56 rounded-full bg-white/10"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute -right-24 -bottom-24 size-64 rounded-full bg-white/5"
            />

            <div className="relative flex flex-wrap items-center justify-between gap-6">
                <div className="max-w-xl">
                    <h2 className="text-2xl font-semibold tracking-tight">
                        {greeting()}
                        {firstName ? `, ${firstName}` : ''}
                    </h2>

                    <p className="mt-1 text-sm text-white/85">{message}</p>
                </div>

                <div className="flex gap-3">
                    <div className="rounded-xl bg-white/15 px-4 py-2">
                        <p className="text-xs text-white/80">Casos abiertos</p>

                        <p className="text-xl font-semibold tabular-nums">
                            {summary.open_tickets.toLocaleString('es-CO')}
                        </p>
                    </div>

                    <div className="rounded-xl bg-white/15 px-4 py-2">
                        <p className="text-xs text-white/80">
                            Preventivos completados
                        </p>

                        <p className="text-xl font-semibold tabular-nums">
                            {summary.completed_preventive} /{' '}
                            {summary.total_preventive}
                        </p>
                    </div>
                </div>
            </div>
        </section>
    );
}

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
                <WelcomeBanner summary={summary} />

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
