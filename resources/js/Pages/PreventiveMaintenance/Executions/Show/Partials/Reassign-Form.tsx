import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent } from 'react';

import { Button } from '@/Components/Ui/Button';
import { Field, FieldError, FieldLabel } from '@/Components/Ui/Field';
import { Input } from '@/Components/Ui/Input';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import type { PreventiveUser } from '@/Types/PreventiveMaintenance/Common';

interface Props {
    executionId: number;
    technicians: PreventiveUser[];
    currentTechnicianId: number | null;
}

export function ReassignForm({
    executionId,
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
            preventiveExecutionsRoutes.assignment.update.url(executionId),
            {
                assigned_to_id: Number(assignedTo),
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
                    className={SELECT_CLASS}
                    aria-invalid={!!errors.assigned_to_id}
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

                <FieldError>{errors.assigned_to_id}</FieldError>
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
                    aria-invalid={!!errors.note}
                />

                <FieldError>{errors.note}</FieldError>
            </Field>

            <Button onClick={submit} disabled={processing || assignedTo === ''}>
                {processing && <LoaderCircle className="animate-spin" />}
                Reasignar ejecución
            </Button>
        </div>
    );
}
