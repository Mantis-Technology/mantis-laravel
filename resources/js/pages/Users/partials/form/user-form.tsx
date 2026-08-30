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

interface UserFormProps {
    method: 'post' | 'put';
    action: string;
    roles: NamedOption[];
    permissions: NamedOption[];
    initialData?: {
        name?: string;
        username?: string;
        email?: string;
    };
    initialRoleIds: number[];
    initialPermissionIds: number[];
}

export function UserForm({
    method,
    action,
    roles,
    permissions,
    initialData,
    initialRoleIds,
    initialPermissionIds,
}: UserFormProps) {
    const initialRoleSet = new Set(initialRoleIds);
    const initialPermissionSet = new Set(initialPermissionIds);
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
                        <FieldLabel htmlFor="name">Nombre completo</FieldLabel>

                        <Input
                            id="name"
                            name="name"
                            defaultValue={initialData?.name ?? ''}
                            placeholder="Ej. Carlos Pérez"
                            disabled={processing}
                            aria-invalid={!!errors.name}
                        />

                        <FieldError>{errors.name}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="username">
                            Nombre de usuario
                        </FieldLabel>

                        <Input
                            id="username"
                            name="username"
                            defaultValue={initialData?.username ?? ''}
                            placeholder="Ej. cperez"
                            autoComplete="off"
                            disabled={processing}
                            aria-invalid={!!errors.username}
                        />

                        <FieldError>{errors.username}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="email">
                            Correo electrónico
                        </FieldLabel>

                        <Input
                            id="email"
                            name="email"
                            type="email"
                            defaultValue={initialData?.email ?? ''}
                            placeholder="Ej. cperez@empresa.com"
                            disabled={processing}
                            aria-invalid={!!errors.email}
                        />

                        <FieldError>{errors.email}</FieldError>
                    </Field>

                    {!isEdit && (
                        <>
                            <Field>
                                <FieldLabel htmlFor="password">
                                    Contraseña
                                </FieldLabel>

                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    disabled={processing}
                                    aria-invalid={!!errors.password}
                                />

                                <FieldError>{errors.password}</FieldError>
                            </Field>

                            <Field>
                                <FieldLabel htmlFor="password_confirmation">
                                    Confirmar contraseña
                                </FieldLabel>

                                <Input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    autoComplete="new-password"
                                    disabled={processing}
                                    aria-invalid={
                                        !!errors.password_confirmation
                                    }
                                />

                                <FieldError>
                                    {errors.password_confirmation}
                                </FieldError>
                            </Field>
                        </>
                    )}

                    <Field>
                        <FieldLabel>Roles</FieldLabel>

                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            {roles.map((role) => (
                                <label
                                    key={role.id}
                                    className="flex items-center gap-2 rounded-md border border-input px-3 py-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="roles[]"
                                        value={role.id}
                                        defaultChecked={initialRoleSet.has(
                                            role.id,
                                        )}
                                        disabled={processing}
                                        className="size-4 rounded border-input accent-primary"
                                    />

                                    <span>{role.name}</span>
                                </label>
                            ))}
                        </div>

                        <FieldError>{errors.roles}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel>Permisos directos</FieldLabel>

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
                                        disabled={processing}
                                        className="size-4 rounded border-input accent-primary"
                                    />

                                    <span>{permission.name}</span>
                                </label>
                            ))}
                        </div>

                        <FieldError>{errors.permissions}</FieldError>
                    </Field>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}

                            {isEdit ? 'Actualizar usuario' : 'Crear usuario'}
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
