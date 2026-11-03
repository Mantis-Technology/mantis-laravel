import { TicketStatusBadge } from '@/Components/Tickets/Ticket-Status-Badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/Components/Ui/Card';
import type { TicketClosure } from '@/Types/Tickets/Closure';

interface Props {
    closure: TicketClosure;
}

const formatDateTime = (date: string) =>
    new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));

export function ClosureSummary({ closure }: Props) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Cierre del caso</CardTitle>

                <CardDescription>
                    Cerrado por {closure.closed_by?.name ?? '—'} el{' '}
                    {formatDateTime(closure.closed_at)}.
                </CardDescription>
            </CardHeader>

            <CardContent className="space-y-4 text-sm">
                <div className="space-y-1">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Resultado
                    </p>

                    <TicketStatusBadge
                        label={closure.result_label}
                        color={
                            closure.result === 'solved_with_recommendations'
                                ? 'info'
                                : 'success'
                        }
                    />
                </div>

                <div className="space-y-1">
                    <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                        Trabajo realizado
                    </p>

                    <p className="whitespace-pre-line">
                        {closure.work_performed}
                    </p>
                </div>

                {closure.observations && (
                    <div className="space-y-1">
                        <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Observaciones para futuras intervenciones
                        </p>

                        <p className="whitespace-pre-line">
                            {closure.observations}
                        </p>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
