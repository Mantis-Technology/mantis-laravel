import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ChangeEvent, ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import ticketsRoutes from '@/routes/tickets';
import type {
    TicketMaintenanceCategoryOption,
    TicketStatusOption,
    TicketTransition,
    TicketUser,
} from '@/types/tickets/ticket';

interface Props {
    ticketId: number;
    transitions: TicketTransition[];
    technicians: TicketUser[];
    maintenanceCategories: TicketMaintenanceCategoryOption[];
    maintenanceTypes: TicketStatusOption[];
    priorities: TicketStatusOption[];
    isFinal: boolean;
}

function renderCategoryOptions(
    categories: TicketMaintenanceCategoryOption[],
    depth: number,
): ReactNode[] {
    return categories.flatMap((category) => [
        <option key={category.id} value={category.id}>
            {'\u00A0\u00A0'.repeat(depth)}
            {category.name}
        </option>,
        ...(category.children?.length
            ? renderCategoryOptions(category.children, depth + 1)
            : []),
    ]);
}

export function StatusActions({
    ticketId,
    transitions,
    technicians,
    maintenanceCategories,
    maintenanceTypes,
    priorities,
    isFinal,
}: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [assignedTo, setAssignedTo] = useState('');
    const [maintenanceCategoryId, setMaintenanceCategoryId] = useState('');
    const [maintenanceType, setMaintenanceType] = useState('');
    const [priority, setPriority] = useState('');
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
    const classificationTransition = transitions.find(
        (transition) => transition.value === 'categorized',
    );
    const actionTransitions = transitions.filter(
        (transition) =>
            !transition.requires_assignee && transition.value !== 'categorized',
    );
    const isProcessing = processing !== null;

    const classificationIncomplete =
        classificationTransition !== undefined &&
        (maintenanceCategoryId === '' ||
            maintenanceType === '' ||
            priority === '');

    const submit = (transition: TicketTransition) => {
        if (transition.requires_assignee && assignedTo === '') {
            return;
        }

        if (transition.value === 'categorized' && classificationIncomplete) {
            return;
        }

        const payload: Record<string, string | number | undefined> = {
            status: transition.value,
            note: note.trim() === '' ? undefined : note.trim(),
        };

        if (transition.requires_assignee) {
            payload.assigned_to = Number(assignedTo);
        }

        if (transition.value === 'categorized') {
            payload.maintenance_category_id = Number(maintenanceCategoryId);
            payload.maintenance_type = maintenanceType;
            payload.priority = priority;
        }

        setProcessing(transition.value);

        router.patch(ticketsRoutes.status.update.url(ticketId), payload, {
            preserveScroll: true,
            onSuccess: () => {
                setNote('');
                setAssignedTo('');
            },
            onFinish: () => setProcessing(null),
        });
    };

    return (
        <div className="space-y-4">
            {classificationTransition && (
                <div className="space-y-4 rounded-md border bg-muted/30 p-3">
                    <p className="text-sm font-medium">
                        Clasificación del caso
                    </p>

                    <Field>
                        <FieldLabel htmlFor="maintenance_category_id">
                            Categoría
                        </FieldLabel>

                        <select
                            id="maintenance_category_id"
                            value={maintenanceCategoryId}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                setMaintenanceCategoryId(event.target.value)
                            }
                            disabled={isProcessing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.maintenance_category_id}
                        >
                            <option value="">
                                Selecciona una categoría...
                            </option>

                            {renderCategoryOptions(maintenanceCategories, 0)}
                        </select>

                        <FieldError>
                            {errors.maintenance_category_id}
                        </FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="maintenance_type">
                            Tipo de mantenimiento
                        </FieldLabel>

                        <select
                            id="maintenance_type"
                            value={maintenanceType}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                setMaintenanceType(event.target.value)
                            }
                            disabled={isProcessing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.maintenance_type}
                        >
                            <option value="">Selecciona un tipo...</option>

                            {maintenanceTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.maintenance_type}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="priority">
                            Nivel de atención
                        </FieldLabel>

                        <select
                            id="priority"
                            value={priority}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                setPriority(event.target.value)
                            }
                            disabled={isProcessing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.priority}
                        >
                            <option value="">Selecciona un nivel...</option>

                            {priorities.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.priority}</FieldError>
                    </Field>
                </div>
            )}

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
                {classificationTransition && (
                    <Button
                        onClick={() => submit(classificationTransition)}
                        disabled={isProcessing || classificationIncomplete}
                    >
                        {processing === classificationTransition.value && (
                            <LoaderCircle className="animate-spin" />
                        )}

                        {classificationTransition.action_label}
                    </Button>
                )}

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
