import { Link, router } from '@inertiajs/react';
import { EllipsisVerticalIcon, Plus } from 'lucide-react';

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
import usersRoutes from '@/routes/users';

interface UserListItem {
    id: number;
    name: string;
    username: string;
    email: string;
    roles: string[];
    is_active: boolean;
    created_at: string | null;
}

interface Props {
    [key: string]: unknown;
    users: UserListItem[];
}

export default function UsersIndex({ users }: Props) {
    const toggleUser = (user: UserListItem) => {
        if (
            !window.confirm(
                user.is_active
                    ? `¿Desactivar a ${user.name}?`
                    : `¿Reactivar a ${user.name}?`,
            )
        ) {
            return;
        }

        if (user.is_active) {
            router.delete(usersRoutes.destroy(user.id).url);
        } else {
            router.post(usersRoutes.restore(user.id).url);
        }
    };

    return (
        <div className="container mx-auto py-10">
            <div className="mb-8 flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Usuarios
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Administra los usuarios del tenant, sus roles y sus
                        permisos.
                    </p>
                </div>

                <Link
                    href={usersRoutes.create.url()}
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4" />
                    Nuevo usuario
                </Link>
            </div>

            <div className="overflow-hidden rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nombre</TableHead>
                            <TableHead>Correo</TableHead>
                            <TableHead>Roles</TableHead>
                            <TableHead>Estado</TableHead>
                            <TableHead className="text-right">
                                Acciones
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        {users.length ? (
                            users.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell>
                                        <div>
                                            <div className="font-medium">
                                                {user.name}
                                            </div>

                                            <div className="text-sm text-muted-foreground">
                                                {user.username}
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell className="text-muted-foreground">
                                        {user.email}
                                    </TableCell>

                                    <TableCell>
                                        <div className="flex flex-wrap gap-1.5">
                                            {user.roles.length ? (
                                                user.roles.map((role) => (
                                                    <Badge
                                                        key={role}
                                                        variant="outline"
                                                    >
                                                        {roleLabel(role)}
                                                    </Badge>
                                                ))
                                            ) : (
                                                <span className="text-sm text-muted-foreground">
                                                    Sin roles
                                                </span>
                                            )}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        {user.is_active ? (
                                            <Badge variant="secondary">
                                                Activa
                                            </Badge>
                                        ) : (
                                            <Badge variant="destructive">
                                                Desactivada
                                            </Badge>
                                        )}
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
                                                                usersRoutes.edit(
                                                                    user.id,
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
                                                        toggleUser(user)
                                                    }
                                                >
                                                    {user.is_active
                                                        ? 'Desactivar'
                                                        : 'Reactivar'}
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell
                                    colSpan={5}
                                    className="h-24 text-center"
                                >
                                    No hay usuarios.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
        </div>
    );
}
