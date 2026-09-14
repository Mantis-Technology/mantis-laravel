import { Link, router } from '@inertiajs/react';
import { History, Plus } from 'lucide-react';
import { useState } from 'react';

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

import type { AssetCardTemplateListItem } from '@/types/assetCardTemplates/assetCardTemplate';

interface Props {
    templates: AssetCardTemplateListItem[];
    createUrl: string;
}

export default function AssetCardTemplatesIndex({
    templates,
    createUrl,
}: Props) {
    const [processingId, setProcessingId] = useState<number | null>(null);

    function createVersion(template: AssetCardTemplateListItem) {
        setProcessingId(template.id);

        router.post(
            template.store_version_url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessingId(null),
            },
        );
    }

    return (
        <div className="container mx-auto max-w-5xl py-10">
            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Plantillas de activos
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Define secciones y campos para tus fichas de activos y
                        gestiona sus versiones.
                    </p>
                </div>

                <Button render={<Link href={createUrl} />}>
                    <Plus /> Crear plantilla
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Plantillas</CardTitle>

                    <CardDescription>
                        {templates.length === 0
                            ? 'Todavía no hay plantillas creadas.'
                            : `${templates.length} plantilla(s) registradas.`}
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    {templates.length === 0 ? (
                        <div className="flex flex-col items-center gap-3 py-10 text-center">
                            <p className="text-sm text-muted-foreground">
                                Crea tu primera plantilla para empezar a definir
                                secciones y campos.
                            </p>

                            <Button
                                variant="outline"
                                render={<Link href={createUrl} />}
                            >
                                <Plus /> Crear plantilla
                            </Button>
                        </div>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Descripción</TableHead>
                                    <TableHead>Versión</TableHead>
                                    <TableHead>Secciones</TableHead>
                                    <TableHead className="text-right">
                                        Acciones
                                    </TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {templates.map((template) => (
                                    <TableRow key={template.id}>
                                        <TableCell className="font-medium">
                                            {template.name}
                                        </TableCell>

                                        <TableCell className="max-w-xs truncate text-muted-foreground">
                                            {template.description ?? '—'}
                                        </TableCell>

                                        <TableCell>
                                            <Badge variant="outline">
                                                v{template.version}
                                            </Badge>
                                        </TableCell>

                                        <TableCell>
                                            {template.sections_count}
                                        </TableCell>

                                        <TableCell>
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={
                                                                template.builder_url
                                                            }
                                                        />
                                                    }
                                                >
                                                    Abrir builder
                                                </Button>

                                                <Button
                                                    variant="secondary"
                                                    size="sm"
                                                    disabled={
                                                        processingId ===
                                                        template.id
                                                    }
                                                    onClick={() =>
                                                        createVersion(template)
                                                    }
                                                >
                                                    <History /> Nueva versión
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
