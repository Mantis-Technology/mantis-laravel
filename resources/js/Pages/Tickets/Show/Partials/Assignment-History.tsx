import type { TicketAssignment } from '@/Types/Tickets/Ticket';

interface Props {
    assignments: TicketAssignment[];
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

export function AssignmentHistory({ assignments }: Props) {
    if (assignments.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                El caso aún no ha tenido un técnico responsable.
            </p>
        );
    }

    return (
        <ol className="space-y-3">
            {[...assignments].reverse().map((assignment) => (
                <li
                    key={assignment.id}
                    className="space-y-1 rounded-md border p-3 text-sm"
                >
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <span className="font-medium">
                            {assignment.technician?.name ?? 'Usuario eliminado'}
                        </span>

                        <span
                            className={
                                assignment.is_active
                                    ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                    : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                            }
                        >
                            {assignment.is_active ? 'Responsable actual' : 'Finalizada'}
                        </span>
                    </div>

                    <p className="text-xs text-muted-foreground">
                        Desde {formatDate(assignment.assigned_at)}
                        {assignment.ended_at &&
                            ` hasta ${formatDate(assignment.ended_at)}`}
                        {assignment.assigned_by &&
                            ` · asignado por ${assignment.assigned_by.name}`}
                    </p>

                    {assignment.note && (
                        <p className="text-xs text-muted-foreground">
                            «{assignment.note}»
                        </p>
                    )}
                </li>
            ))}
        </ol>
    );
}