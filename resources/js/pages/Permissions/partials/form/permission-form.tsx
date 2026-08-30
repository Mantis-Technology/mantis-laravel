import { Form } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

interface PermissionFormProps {
    method: 'post' | 'put';
    action: string;
    initialData?: {
        name?: string;
    };
}

export function PermissionForm({
    method,
    action,
    initialData,
}: PermissionFormProps) {
    const isEdit = method === 'put';

    return (
        <Form
            action={action}
            method={method}
            disableWhileProcessing
            className="space-y-6"
        >
            {({ errors, processing }) => (
                <FieldGroup>
                    <Field>
                        <FieldLabel htmlFor="name">Nombre</FieldLabel>

                        <Input
                            id="name"
                            name="name"
                            defaultValue={initialData?.name ?? ''}
                            placeholder="Ej. action:create_reports"
                            disabled={processing}
                            aria-invalid={!!errors.name}
                        />

                        <p className="text-sm text-muted-foreground">
                            Usa el prefijo{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">
                                query:
                            </code>{' '}
                            para permisos de consulta y{' '}
                            <code className="rounded bg-muted px-1 py-0.5 text-xs">
                                action:
                            </code>{' '}
                            para acciones.
                        </p>

                        <FieldError>{errors.name}</FieldError>
                    </Field>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}

                            {isEdit ? 'Actualizar permiso' : 'Crear permiso'}
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
