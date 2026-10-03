import { TicketStatusBadge } from '@/components/tickets/ticket-status-badge';
import type { TicketStatusHistoryEntry } from '@/types/tickets/ticket';

const formatDate = (date: string | null) => {
    if (!date) {
        return '—';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

interface Props {
    transitions: TicketStatusHistoryEntry[];
}

export function StatusTimeline({ transitions }: Props) {
    if (transitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                El caso todavía no registra cambios de estado.
            </p>
        );
    }

    return (
        <ol className="relative space-y-6 border-l border-border pl-6">
            {transitions.map((transition) => (
                <li key={transition.id} className="relative">
                    <span className="absolute top-1 -left-[31px] size-2.5 rounded-full border-2 border-background bg-muted-foreground" />

                    <div className="flex flex-wrap items-center gap-2">
                        <TicketStatusBadge
                            label={transition.to_label}
                            color={transition.to_color}
                        />

                        <span className="text-sm text-muted-foreground">
                            {transition.from_label
                                ? `Desde ${transition.from_label}`
                                : 'Caso reportado'}
                        </span>
                    </div>

                    <p className="mt-1 text-xs text-muted-foreground">
                        {transition.changed_by?.name ?? 'Sistema'} ·{' '}
                        {formatDate(transition.created_at)}
                    </p>

                    {transition.note && (
                        <p className="mt-2 text-sm">{transition.note}</p>
                    )}
                </li>
            ))}
        </ol>
    );
}
