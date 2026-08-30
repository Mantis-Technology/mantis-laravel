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

interface NamedOption {
    id: number;
    name: string;
}

interface RoleFormProps {
    method: 'post' | 'put';
    action: string;
    permissions: NamedOption[];
    initialData?: {
        name?: string;
        is_protected?: boolean;
    };
    initialPermissionIds: number[];
}

export function RoleForm({
    method,
    action,
    permissions,
    initialData,
    initialPermissionIds,
}: RoleFormProps) {
    const initialPermissionSet = new Set(initialPermissionIds);
    const isEdit = method === 'put';
    const isReadOnly = isEdit && !!initialData?.is_protected;

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
                            placeholder="Ej. supervisor"
                            disabled={processing || isReadOnly}
                            aria-invalid={!!errors.name}
                        />

                        <FieldError>{errors.name}</FieldError>
                    </Field>

                    {isReadOnly && (
                        <p className="text-sm text-muted-foreground">
                            El rol de administrador del tenant es un rol
                            protegido y no puede modificarse.
                        </p>
                    )}

                    <Field>
                        <FieldLabel>Permisos</FieldLabel>

                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {permissions.map((permission) => (
                                <label
                                    key={permission.id}
                                    className="flex items-center gap-2 rounded-md border border-input px-3 py-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value={permission.id}
                                        defaultChecked={initialPermissionSet.has(
                                            permission.id,
                                        )}
                                        disabled={processing || isReadOnly}
                                        className="size-4 rounded border-input accent-primary"
                                    />

                                    <span>{permission.name}</span>
                                </label>
                            ))}
                        </div>

                        <FieldError>{errors.permissions}</FieldError>
                    </Field>

                    <div className="flex justify-end">
                        <Button
                            type="submit"
                            disabled={processing || isReadOnly}
                        >
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}

                            {isEdit ? 'Actualizar rol' : 'Crear rol'}
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
