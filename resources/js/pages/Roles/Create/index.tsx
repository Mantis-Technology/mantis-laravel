import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import rolesRoutes from '@/routes/roles';

import { RoleForm } from '../partials/form/role-form';

interface NamedOption {
    id: number;
    name: string;
}

interface Props {
    action: string;
    permissions: NamedOption[];
}

export default function RolesCreate({ action, permissions }: Props) {
    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={rolesRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a roles
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nuevo rol
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Crea un rol y asigna los permisos que lo componen.
                </p>
            </div>

            <Card>
                <CardContent className="space-y-6 pt-6">
                    <RoleForm
                        method="post"
                        action={action}
                        permissions={permissions}
                        initialPermissionIds={[]}
                    />
                </CardContent>
            </Card>
        </div>
    );
}
