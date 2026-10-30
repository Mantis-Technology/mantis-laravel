import { router, usePage } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/Ui/Button';
import { Field, FieldError, FieldLabel } from '@/Components/Ui/Field';
import { Input } from '@/Components/Ui/Input';
import { todayLocalIso } from '@/Lib/Preventive-Date';
import preventiveExecutionsRoutes from '@/routes/preventive-executions';

interface Props {
    executionId: number;
}

export function PostponeForm({ executionId }: Props) {
    const errors = usePage<{ errors: Record<string, string> }>().props.errors;

    const [scheduledAt, setScheduledAt] = useState('');
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);

    const submit = () => {
        if (scheduledAt === '' || note.trim() === '') {
            return;
        }

        setProcessing(true);

        router.patch(
            preventiveExecutionsRoutes.schedule.update.url(executionId),
            { scheduled_at: scheduledAt, note: note.trim() },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setScheduledAt('');
                    setNote('');
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <div className="space-y-4">
            <Field>
                <FieldLabel htmlFor="postpone_date">Nueva fecha</FieldLabel>

                <Input
                    id="postpone_date"
                    type="date"
                    min={todayLocalIso()}
                    value={scheduledAt}
                    onChange={(event) => setScheduledAt(event.target.value)}
                    disabled={processing}
                    aria-invalid={!!errors.scheduled_at}
                />

                <FieldError>{errors.scheduled_at}</FieldError>
            </Field>

            <Field>
                <FieldLabel htmlFor="postpone_note">Motivo</FieldLabel>

                <Input
                    id="postpone_note"
                    value={note}
                    onChange={(event) => setNote(event.target.value)}
                    maxLength={500}
                    disabled={processing}
                    placeholder="Motivo del aplazamiento"
                    aria-invalid={!!errors.note}
                />

                <FieldError>{errors.note}</FieldError>
            </Field>

            <Button
                onClick={submit}
                disabled={processing || scheduledAt === '' || note.trim() === ''}
            >
                {processing && <LoaderCircle className="animate-spin" />}
                Aplazar ejecución
            </Button>
        </div>
    );
}
