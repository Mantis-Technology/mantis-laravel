import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import ticketsRoutes from '@/routes/tickets';
import type { TicketTransition, TicketUser } from '@/types/tickets/ticket';

interface Props {
    ticketId: number;
    transitions: TicketTransition[];
    technicians: TicketUser[];
    isFinal: boolean;
}

export function StatusActions({
    ticketId,
    transitions,
    technicians,
    isFinal,
}: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [assignedTo, setAssignedTo] = useState('');
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState<string | null>(null);

    if (transitions.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                {isFinal
                    ? 'El caso está en un estado final y ya no admite cambios.'
                    : 'No tienes permisos para gestionar este caso.'}
            </p>
        );
    }

    const assignTransition = transitions.find(
        (transition) => transition.requires_assignee,
    );
    const actionTransitions = transitions.filter(
        (transition) => !transition.requires_assignee,
    );
    const isProcessing = processing !== null;

    const submit = (transition: TicketTransition) => {
        if (transition.requires_assignee && assignedTo === '') {
            return;
        }

        setProcessing(transition.value);

        router.patch(
            ticketsRoutes.status.update.url(ticketId),
            {
                status: transition.value,
                assigned_to: transition.requires_assignee
                    ? Number(assignedTo)
                    : undefined,
                note: note.trim() === '' ? undefined : note.trim(),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setNote('');
                    setAssignedTo('');
                },
                onFinish: () => setProcessing(null),
            },
        );
    };

    return (
        <div className="space-y-4">
            {assignTransition && (
                <Field>
                    <FieldLabel htmlFor="assigned_to">
                        Técnico responsable
                    </FieldLabel>

                    <select
                        id="assigned_to"
                        value={assignedTo}
                        onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                            setAssignedTo(event.target.value)
                        }
                        disabled={isProcessing}
                        className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <option value="">Selecciona un técnico...</option>

                        {technicians.map((technician) => (
                            <option key={technician.id} value={technician.id}>
                                {technician.name}
                            </option>
                        ))}
                    </select>

                    {technicians.length === 0 && (
                        <p className="text-xs text-muted-foreground">
                            No hay usuarios con rol de técnico.
                        </p>
                    )}

                    <FieldError>{errors.assigned_to}</FieldError>
                </Field>
            )}

            <Field>
                <FieldLabel htmlFor="note">Nota (opcional)</FieldLabel>

                <Input
                    id="note"
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    maxLength={500}
                    disabled={isProcessing}
                    placeholder="Motivo o comentario del cambio"
                />

                <FieldError>{errors.note}</FieldError>
            </Field>

            <div className="flex flex-wrap gap-2">
                {assignTransition && (
                    <Button
                        onClick={() => submit(assignTransition)}
                        disabled={isProcessing || assignedTo === ''}
                    >
                        {processing === assignTransition.value && (
                            <LoaderCircle className="animate-spin" />
                        )}

                        {assignTransition.action_label}
                    </Button>
                )}

                {actionTransitions.map((transition) => (
                    <Button
                        key={transition.value}
                        variant={
                            transition.value === 'cancelled'
                                ? 'destructive'
                                : 'outline'
                        }
                        onClick={() => submit(transition)}
                        disabled={isProcessing}
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
