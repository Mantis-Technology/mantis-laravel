import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import { formatPreventiveDateTime } from '@/Lib/Preventive-Date';
import type { ExecutionStatusTransition } from '@/Types/PreventiveMaintenance/Execution';

interface Props {
    transitions: ExecutionStatusTransition[];
}

export function StatusTimeline({ transitions }: Props) {
    if (transitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Aún no hay cambios de estado registrados.
            </p>
        );
    }

    return (
        <ol className="space-y-4">
            {transitions.map((transition) => (
                <li
                    key={transition.id}
                    className="space-y-1 border-l-2 pl-4 text-sm"
                >
                    <div className="flex flex-wrap items-center gap-2">
                        {transition.from_label && (
                            <>
                                <span className="text-muted-foreground">
                                    {transition.from_label}
                                </span>
                                <span className="text-muted-foreground">→</span>
                            </>
                        )}

                        <TicketStatusBadge
                            label={transition.to_label}
                            color={transition.to_color}
                        />
                    </div>

                    <p className="text-xs text-muted-foreground">
                        {formatPreventiveDateTime(transition.created_at)}
                        {transition.changed_by &&
                            ` · ${transition.changed_by.name}`}
                    </p>

                    {transition.note && (
                        <p className="whitespace-pre-line">{transition.note}</p>
                    )}
                </li>
            ))}
        </ol>
    );
}
