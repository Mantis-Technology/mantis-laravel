import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/Ui/Button';
import { Field, FieldError, FieldLabel } from '@/Components/Ui/Field';
import { Input } from '@/Components/Ui/Input';
import { Textarea } from '@/Components/Ui/Textarea';
import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import type { ExecutionTransition } from '@/Types/PreventiveMaintenance/Execution';

interface Props {
    executionId: number;
    transitions: ExecutionTransition[];
    isFinal: boolean;
}

export function StatusActions({ executionId, transitions, isFinal }: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [note, setNote] = useState('');
    const [executionNotes, setExecutionNotes] = useState('');
    const [findings, setFindings] = useState('');
    const [processing, setProcessing] = useState<string | null>(null);

    if (transitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {isFinal
                    ? 'La ejecución está en un estado final y ya no admite cambios.'
                    : 'No tienes acciones disponibles para esta ejecución.'}
            </p>
        );
    }

    const isProcessing = processing !== null;
    const completeTransition = transitions.find(
        (transition) => transition.requires_notes,
    );

    const isMissingData = (transition: ExecutionTransition) =>
        (transition.requires_reason && note.trim() === '') ||
        (transition.requires_notes && executionNotes.trim() === '');

    const submit = (transition: ExecutionTransition) => {
        if (isMissingData(transition)) {
            return;
        }

        const payload: Record<string, string | undefined> = {
            status: transition.value,
            note: note.trim() === '' ? undefined : note.trim(),
        };

        if (transition.requires_notes) {
            payload.execution_notes = executionNotes.trim();
            payload.findings =
                findings.trim() === '' ? undefined : findings.trim();
        }

        setProcessing(transition.value);

        router.patch(
            preventiveExecutionsRoutes.status.update.url(executionId),
            payload,
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNote('');
                    setExecutionNotes('');
                    setFindings('');
                },
                onFinish: () => setProcessing(null),
            },
        );
    };

    return (
        <div className="space-y-4">
            {completeTransition && (
                <div className="space-y-4 rounded-md border bg-muted/30 p-3">
                    <p className="text-sm font-medium">
                        Resultado de la ejecución
                    </p>

                    <Field>
                        <FieldLabel htmlFor="execution_notes">
                            Notas de ejecución (obligatorias para completar)
                        </FieldLabel>

                        <Textarea
                            id="execution_notes"
                            value={executionNotes}
                            onChange={(event) =>
                                setExecutionNotes(event.target.value)
                            }
                            rows={4}
                            maxLength={5000}
                            disabled={isProcessing}
                            placeholder="Qué se hizo durante el mantenimiento..."
                            aria-invalid={!!errors.execution_notes}
                        />

                        <FieldError>{errors.execution_notes}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="findings">
                            Hallazgos (opcional)
                        </FieldLabel>

                        <Textarea
                            id="findings"
                            value={findings}
                            onChange={(event) => setFindings(event.target.value)}
                            rows={3}
                            maxLength={5000}
                            disabled={isProcessing}
                            placeholder="Novedades o problemas encontrados..."
                            aria-invalid={!!errors.findings}
                        />

                        <FieldError>{errors.findings}</FieldError>
                    </Field>
                </div>
            )}

            <Field>
                <FieldLabel htmlFor="status_note">
                    Motivo o nota (obligatorio al omitir o cancelar)
                </FieldLabel>

                <Input
                    id="status_note"
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    maxLength={500}
                    disabled={isProcessing}
                    placeholder="Motivo o comentario del cambio"
                    aria-invalid={!!errors.note}
                />

                <FieldError>{errors.note}</FieldError>
            </Field>

            <div className="flex flex-wrap gap-2">
                {transitions.map((transition) => (
                    <Button
                        key={transition.value}
                        variant={
                            transition.value === 'cancelled'
                                ? 'destructive'
                                : transition.requires_notes ||
                                    transition.value === 'in_progress'
                                  ? 'default'
                                  : 'outline'
                        }
                        onClick={() => submit(transition)}
                        disabled={isProcessing || isMissingData(transition)}
                    >
                        {processing === transition.value && (
                            <LoaderCircle className="animate-spin" />
                        )}

                        {transition.action_label}
                    </Button>
                ))}
            </div>
        </div>
    );
}
