import { Link, router } from '@inertiajs/react';
import { EllipsisVerticalIcon, Plus, Trash2Icon } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import permissionsRoutes from '@/routes/permissions';

interface PermissionListItem {
    id: number;
    name: string;
    guard_name: string;
    roles_count: number;
}

interface Props {
    [key: string]: unknown;
    permissions: PermissionListItem[];
}

export default function PermissionsIndex({ permissions }: Props) {
    const destroyPermission = (permission: PermissionListItem) => {
        if (
            !window.confirm(
                `¿Eliminar el permiso "${permission.name}"? Se quitará de todos los roles.`,
            )
        ) {
            return;
        }

        router.delete(permissionsRoutes.destroy(permission.id).url);
    };

    return (
        <div className="container mx-auto py-10">
            <div className="mb-8 flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Permisos
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Administra los permisos disponibles dentro del tenant.
                    </p>
                </div>

                <Link
                    href={permissionsRoutes.create.url()}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4" />
                    Nuevo permiso
                </Link>
            </div>

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Guard</TableHead>
                            <TableHead>Roles</TableHead>
                            <TableHead className="text-right">
                                Acciones
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {permissions.length ? (
                            permissions.map((permission) => (
                                <TableRow key={permission.id}>
                                    <TableCell className="font-medium">
                                        {permission.name}
                                    </TableCell>

                                    <TableCell>
                                        <Badge variant="outline">
                                            {permission.guard_name}
                                        </Badge>
                                    </TableCell>

                                    <TableCell className="text-muted-foreground">
                                        {permission.roles_count}
                                    </TableCell>

                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger
                                                render={
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-muted-foreground"
                                                    />
                                                }
                                            >
                                                <EllipsisVerticalIcon className="size-4" />

                                                <span className="sr-only">
                                                    Abrir acciones
                                                </span>
                                            </DropdownMenuTrigger>

                                            <DropdownMenuContent
                                                align="end"
                                                className="w-40"
                                            >
                                                <DropdownMenuItem
                                                    render={
                                                        <Link
                                                            href={
                                                                permissionsRoutes.edit(
                                                                    permission.id,
                                                                ).url
                                                            }
                                                        />
                                                    }
                                                >
                                                    Editar
                                                </DropdownMenuItem>

                                                <DropdownMenuSeparator />

                                                <DropdownMenuItem
                                                    variant="destructive"
                                                    onClick={() =>
                                                        destroyPermission(
                                                            permission,
                                                        )
                                                    }
                                                >
                                                    <Trash2Icon className="size-4" />
                                                    Eliminar
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={4}
                                    className="h-24 text-center"
                                >
                                    No hay permisos.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
