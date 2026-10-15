import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent } from 'react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import ticketsRoutes from '@/routes/tickets';
import type { TicketUser } from '@/types/tickets/ticket';

interface Props {
    ticketId: number;
    technicians: TicketUser[];
    currentTechnicianId: number | null;
}

export function ReassignForm({
    ticketId,
    technicians,
    currentTechnicianId,
}: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [assignedTo, setAssignedTo] = useState('');
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);

    const candidates = technicians.filter(
        (technician) => technician.id !== currentTechnicianId,
    );

    const submit = () => {
        if (assignedTo === '') {
            return;
        }

        setProcessing(true);

        router.patch(
            ticketsRoutes.assignment.update.url(ticketId),
            {
                assigned_to: Number(assignedTo),
                note: note.trim() === '' ? undefined : note.trim(),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setAssignedTo('');
                    setNote('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="space-y-4">
            <Field>
                <FieldLabel htmlFor="reassign_to">Nuevo responsable</FieldLabel>

                <select
                    id="reassign_to"
                    value={assignedTo}
                    onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                        setAssignedTo(event.target.value)
                    }
                    disabled={processing}
                    className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    aria-invalid={!!errors.assigned_to}
                >
                    <option value="">Selecciona un técnico...</option>

                    {candidates.map((technician) => (
                        <option key={technician.id} value={technician.id}>
                            {technician.name}
                        </option>
                    ))}
                </select>

                {candidates.length === 0 && (
                    <p className="text-xs text-muted-foreground">
                        No hay otros técnicos disponibles.
                    </p>
                )}

                <FieldError>{errors.assigned_to}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="reassign_note">
                    Motivo (opcional)
                </FieldLabel>

                <Input
                    id="reassign_note"
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    maxLength={500}
                    disabled={processing}
                    placeholder="Motivo de la reasignación"
                />

                <FieldError>{errors.note}</FieldError>
            </Field>

            <Button onClick={submit} disabled={processing || assignedTo === ''}>
                {processing && <LoaderCircle className="animate-spin" />}
                Reasignar caso
            </Button>
        </div>
    );
}