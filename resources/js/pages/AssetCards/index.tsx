import { Link, router } from '@inertiajs/react';
import { Eye, Pencil, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import type { AssetCardListItem } from '@/types/assetCards/assetCard';

interface Props {
    assetCards: AssetCardListItem[];
    createUrl: string;
}

export default function AssetCardsIndex({ assetCards, createUrl }: Props) {
    function destroy(assetCard: AssetCardListItem) {
        router.delete(assetCard.destroy_url, { preserveScroll: true });
    }

    return (
        <div className="container mx-auto max-w-5xl py-10">
            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Fichas de activos
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Gestiona los activos creados a partir de las plantillas.
                    </p>
                </div>

                <Button render={<Link href={createUrl} />}>
                    <Plus /> Nueva ficha
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Fichas</CardTitle>

                    <CardDescription>
                        {assetCards.length === 0
                            ? 'Todavía no hay fichas creadas.'
                            : `${assetCards.length} ficha(s) registradas.`}
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {assetCards.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-10 text-center">
                            <p className="text-sm text-muted-foreground">
                                Crea tu primera ficha a partir de una plantilla.
                            </p>

                            <Button
                                variant="outline"
                                render={<Link href={createUrl} />}
                            >
                                <Plus /> Nueva ficha
                            </Button>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Plantilla</TableHead>
                                    <TableHead>Versión</TableHead>
                                    <TableHead>Actualizado</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {assetCards.map((assetCard) => (
                                    <TableRow key={assetCard.id}>
                                        <TableCell className="font-medium">
                                            {assetCard.code}
                                        </TableCell>

                                        <TableCell className="text-muted-foreground">
                                            {assetCard.template_name ?? '—'}
                                        </TableCell>

                                        <TableCell>
                                            <Badge variant="outline">
                                                v{assetCard.version}
                                            </Badge>
                                        </TableCell>

                                        <TableCell className="text-muted-foreground">
                                            {assetCard.updated_at ?? '—'}
                                        </TableCell>

                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={
                                                                assetCard.show_url
                                                            }
                                                        />
                                                    }
                                                >
                                                    <Eye /> Ver
                                                </Button>

                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={
                                                                assetCard.edit_url
                                                            }
                                                        />
                                                    }
                                                >
                                                    <Pencil /> Editar
                                                </Button>

                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() =>
                                                        destroy(assetCard)
                                                    }
                                                >
                                                    <Trash2 /> Eliminar
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
