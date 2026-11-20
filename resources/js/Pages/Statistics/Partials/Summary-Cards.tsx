import {
    ActivityIcon,
    AlertTriangleIcon,
    CalendarCheckIcon,
    CheckCircle2Icon,
    ClipboardListIcon,
    ClockIcon,
    ShieldCheckIcon,
    TimerIcon,
} from 'lucide-react';
import type { ElementType } from 'react';

import { Card, CardContent } from '@/Components/Ui/Card';
import { cn } from '@/Lib/Utils';
import type { StatisticsSummary } from '@/Types/Statistics/Statistics';

interface Props {
    summary: StatisticsSummary;
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

type Tone = 'primary' | 'teal';

const FILL_CLASSES: Record<Tone, string> = {
    primary:
        'bg-linear-to-br from-[#C16D52] to-[#9A4630] text-white ring-transparent hover:ring-transparent',
    teal: 'bg-linear-to-br from-[#348790] to-[#25636A] text-white ring-transparent hover:ring-transparent',
};

const CHIP_CLASSES: Record<Tone, string> = {
    primary: 'bg-primary/10 text-primary',
    teal: 'bg-teal/10 text-teal',
};

function SummaryCard({
    title,
    value,
    detail,
    icon: Icon,
    fill,
    chip = 'primary',
}: {
    title: string;
    value: string;
    detail: string;
    icon: ElementType;
    fill?: Tone;
    chip?: Tone;
}) {
    const soft = fill ? 'text-white/85' : 'text-muted-foreground';

    return (
        <Card size="sm" className={fill ? FILL_CLASSES[fill] : undefined}>
            <CardContent className="flex items-start justify-between gap-4">
                <div className="space-y-1">
                    <p className={cn('text-sm', soft)}>{title}</p>

                    <p className="text-2xl font-semibold tracking-tight">
                        {value}
                    </p>

                    <p className={cn('text-xs', soft)}>{detail}</p>
                </div>

                <div
                    className={cn(
                        'rounded-lg p-2',
                        fill ? 'bg-white/20 text-white' : CHIP_CLASSES[chip],
                    )}
                >
                    <Icon className="size-4" />
                </div>
            </CardContent>
        </Card>
    );
}

export function SummaryCards({ summary }: Props) {
    return (
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <SummaryCard
                title="Casos registrados"
                value={summary.total_tickets.toLocaleString('es-CO')}
                detail={`${summary.open_tickets} abiertos`}
                icon={ClipboardListIcon}
                fill="primary"
            />

            <SummaryCard
                title="Casos cerrados"
                value={summary.closed_tickets.toLocaleString('es-CO')}
                detail={`${summary.resolved_tickets} resueltos por cerrar`}
                icon={CheckCircle2Icon}
                chip="teal"
            />

            <SummaryCard
                title="Tiempo medio de resolución"
                value={formatHours(summary.average_resolution_hours)}
                detail={`Respuesta: ${formatHours(summary.average_response_hours)}`}
                icon={TimerIcon}
                chip="primary"
            />

            <SummaryCard
                title="Horas trabajadas"
                value={formatHours(summary.total_worked_hours)}
                detail={`${formatHours(summary.ticket_worked_hours)} en casos · ${formatHours(summary.preventive_worked_hours)} en preventivos`}
                icon={ClockIcon}
                fill="teal"
            />

            <SummaryCard
                title="Cumplimiento de SLA"
                value={formatRate(summary.sla_compliance_rate)}
                detail={`${summary.sla_met} cumplidos · ${summary.sla_missed} incumplidos`}
                icon={ShieldCheckIcon}
                chip="teal"
            />

            <SummaryCard
                title="Preventivos completados"
                value={`${summary.completed_preventive} / ${summary.total_preventive}`}
                detail={`${summary.total_preventive - summary.completed_preventive} pendientes o cerrados sin completar`}
                icon={CalendarCheckIcon}
                chip="teal"
            />

            <SummaryCard
                title="Preventivos vencidos"
                value={summary.overdue_preventive.toLocaleString('es-CO')}
                detail="Programados antes de hoy y sin completar"
                icon={AlertTriangleIcon}
                chip="primary"
            />

            <SummaryCard
                title="Casos que requieren atención"
                value={(
                    summary.sla_overdue_tickets + summary.sla_at_risk_tickets
                ).toLocaleString('es-CO')}
                detail={`${summary.sla_overdue_tickets} vencidos · ${summary.sla_at_risk_tickets} por vencer`}
                icon={ActivityIcon}
                chip="primary"
            />
        </div>
    );
}
