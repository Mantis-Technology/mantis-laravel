import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';
import permissionsRoutes from '@/routes/permissions';

import { PermissionForm } from '../partials/form/permission-form';

interface Props {
    action: string;
}

export default function PermissionsCreate({ action }: Props) {
    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={permissionsRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a permisos
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nuevo permiso
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Crea un nuevo permiso que puedas asignar a los roles.
                </p>
            </div>

            <Card>
                <CardContent className="space-y-6 pt-6">
                    <PermissionForm method="post" action={action} />
                </CardContent>
            </Card>
        </div>
    );
}
