import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent } from 'react';

import { Button } from '@/Components/Ui/Button';
import { Field, FieldError, FieldLabel } from '@/Components/Ui/Field';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import ticketsRoutes from '@/routes/tickets';
import type { TicketClosureResultOption } from '@/Types/Tickets/Closure';

const TEXTAREA_CLASS =
    'flex min-h-24 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50';

const MIN_WORK_LENGTH = 10;

interface Props {
    ticketId: number;
    results: TicketClosureResultOption[];
}

export function CloseForm({ ticketId, results }: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [workPerformed, setWorkPerformed] = useState('');
    const [result, setResult] = useState('');
    const [observations, setObservations] = useState('');
    const [noPendingActions, setNoPendingActions] = useState(false);
    const [processing, setProcessing] = useState(false);

    const selectedResult = results.find((option) => option.value === result);
    const observationsRequired = selectedResult?.requires_observations ?? false;

    const incomplete =
        workPerformed.trim().length < MIN_WORK_LENGTH ||
        result === '' ||
        (observationsRequired && observations.trim() === '') ||
        !noPendingActions;

    const submit = () => {
        if (incomplete) {
            return;
        }

        setProcessing(true);

        router.post(
            ticketsRoutes.closure.store.url(ticketId),
            {
                work_performed: workPerformed.trim(),
                result,
                observations:
                    observations.trim() === ''
                        ? undefined
                        : observations.trim(),
                no_pending_actions: noPendingActions,
            },
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="space-y-4">
            <Field>
                <FieldLabel htmlFor="work_performed">
                    Trabajo realizado
                </FieldLabel>

                <textarea
                    id="work_performed"
                    value={workPerformed}
                    onChange={(event: ChangeEvent<HTMLTextAreaElement>) =>
                        setWorkPerformed(event.target.value)
                    }
                    maxLength={5000}
                    disabled={processing}
                    placeholder="Describe qué se hizo, de modo que otra persona lo entienda sin consultarte."
                    className={TEXTAREA_CLASS}
                    aria-invalid={!!errors.work_performed}
                />

                <FieldError>{errors.work_performed}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="result">Resultado</FieldLabel>

                <select
                    id="result"
                    value={result}
                    onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                        setResult(event.target.value)
                    }
                    disabled={processing}
                    className={SELECT_CLASS}
                    aria-invalid={!!errors.result}
                >
                    <option value="">Selecciona un resultado...</option>

                    {results.map((option) => (
                        <option key={option.value} value={option.value}>
                            {option.label}
                        </option>
                    ))}
                </select>

                <FieldError>{errors.result}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="observations">
                    Observaciones para futuras intervenciones
                    {observationsRequired ? '' : ' (opcional)'}
                </FieldLabel>

                <textarea
                    id="observations"
                    value={observations}
                    onChange={(event: ChangeEvent<HTMLTextAreaElement>) =>
                        setObservations(event.target.value)
                    }
                    maxLength={5000}
                    disabled={processing}
                    placeholder="Recomendaciones, repuestos a vigilar, riesgos detectados..."
                    className={TEXTAREA_CLASS}
                    aria-invalid={!!errors.observations}
                />

                <FieldError>{errors.observations}</FieldError>
            </Field>

            <div className="space-y-1">
                <label className="flex items-start gap-2 text-sm">
                    <input
                        type="checkbox"
                        checked={noPendingActions}
                        onChange={(event: ChangeEvent<HTMLInputElement>) =>
                            setNoPendingActions(event.target.checked)
                        }
                        disabled={processing}
                        className="mt-0.5 h-4 w-4"
                    />

                    <span>
                        Confirmo que no quedan acciones pendientes en este caso.
                    </span>
                </label>

                <FieldError>{errors.no_pending_actions}</FieldError>
            </div>

            <Button onClick={submit} disabled={processing || incomplete}>
                {processing && <LoaderCircle className="animate-spin" />}
                Cerrar caso
            </Button>
        </div>
    );
}
