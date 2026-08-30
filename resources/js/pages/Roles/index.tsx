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
import { roleLabel } from '@/lib/access';
import rolesRoutes from '@/routes/roles';

interface RoleListItem {
    id: number;
    name: string;
    permissions: string[];
    is_protected: boolean;
}

interface Props {
    [key: string]: unknown;
    roles: RoleListItem[];
}

export default function RolesIndex({ roles }: Props) {
    const destroyRole = (role: RoleListItem) => {
        if (!window.confirm(`¿Eliminar el rol "${roleLabel(role.name)}"?`)) {
            return;
        }

        router.delete(rolesRoutes.destroy(role.id).url);
    };

    return (
        <div className="container mx-auto py-10">
            <div className="mb-8 flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Roles
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Administra los roles y los permisos que agrupan.
                    </p>
                </div>

                <Link
                    href={rolesRoutes.create.url()}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4" />
                    Nuevo rol
                </Link>
            </div>

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Permisos</TableHead>
                            <TableHead className="text-right">
                                Acciones
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {roles.length ? (
                            roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell>
                                        <div className="font-medium">
                                            {roleLabel(role.name)}
                                        </div>

                                        <div className="text-sm text-muted-foreground">
                                            {role.name}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div className="flex flex-wrap gap-1.5">
                                            {role.permissions.length ? (
                                                role.permissions.map(
                                                    (permission) => (
                                                        <Badge
                                                            key={permission}
                                                            variant="outline"
                                                        >
                                                            {permission}
                                                        </Badge>
                                                    ),
                                                )
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    Sin permisos
                                                </span>
                                            )}
                                        </div>
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
                                                                rolesRoutes.edit(
                                                                    role.id,
                                                                ).url
                                                            }
                                                        />
                                                    }
                                                >
                                                    Editar
                                                </DropdownMenuItem>

                                                {!role.is_protected && (
                                                    <>
                                                        <DropdownMenuSeparator />

                                                        <DropdownMenuItem
                                                            variant="destructive"
                                                            onClick={() =>
                                                                destroyRole(
                                                                    role,
                                                                )
                                                            }
                                                        >
                                                            <Trash2Icon className="size-4" />
                                                            Eliminar
                                                        </DropdownMenuItem>
                                                    </>
                                                )}
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={3}
                                    className="h-24 text-center"
                                >
                                    No hay roles.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
