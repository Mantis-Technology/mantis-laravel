import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import usersRoutes from '@/routes/users';

import { UserForm } from '../partials/form/user-form';

interface NamedOption {
    id: number;
    name: string;
}

interface Props {
    action: string;
    roles: NamedOption[];
    permissions: NamedOption[];
}

export default function UsersCreate({ action, roles, permissions }: Props) {
    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={usersRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a usuarios
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nuevo usuario
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Crea un usuario y asígnale roles y permisos dentro del
                    tenant.
                </p>
            </div>

            <Card>
                <CardContent className="space-y-6 pt-6">
                    <UserForm
                        method="post"
                        action={action}
                        roles={roles}
                        permissions={permissions}
                        initialRoleIds={[]}
                        initialPermissionIds={[]}
                    />
                </CardContent>
            </Card>
        </div>
    );
}
