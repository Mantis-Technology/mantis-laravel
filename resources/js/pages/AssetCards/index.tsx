import { Link, router } from '@inertiajs/react';
import { ArrowRight, Eye, Pencil, Plus, Trash2 } from 'lucide-react';
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
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import type {
    AssetCardListItem,
    AssetTemplateOption,
} from '@/types/assetCards/assetCard';

interface Props {
    assetCards: AssetCardListItem[];
    createUrl: string;
    templates: AssetTemplateOption[];
}

export default function AssetCardsIndex({
    assetCards,
    createUrl,
    templates,
}: Props) {
    const [createOpen, setCreateOpen] = useState(false);
    const [templateId, setTemplateId] = useState<number | null>(null);
    const [version, setVersion] = useState<number | null>(null);

    const selectedOption =
        templates.find((template) => template.id === templateId) ?? null;
    const versions = selectedOption?.versions ?? [];

    function openCreate() {
        setTemplateId(templates[0]?.id ?? null);
        setVersion(templates[0]?.versions[0] ?? null);
        setCreateOpen(true);
    }

    function changeTemplate(value: string | null) {
        const template =
            templates.find((item) => String(item.id) === value) ?? null;

        setTemplateId(template?.id ?? null);
        setVersion(template?.versions[0] ?? null);
    }

    function continueToForm() {
        if (templateId === null || version === null) {
            return;
        }

        router.get(createUrl, { template_id: templateId, version });
    }

    function destroy(assetCard: AssetCardListItem) {
        router.delete(assetCard.destroy_url, { preserveScroll: true });
    }

    return (
        <div className="w-full py-10">
            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Fichas de activos
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Gestiona los activos creados a partir de las plantillas.
                    </p>
                </div>

                <Button type="button" onClick={openCreate}>
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
                                type="button"
                                onClick={openCreate}
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

            <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nueva ficha de activo</DialogTitle>

                        <DialogDescription>
                            Selecciona la plantilla y la versión para continuar
                            con el formulario.
                        </DialogDescription>
                    </DialogHeader>

                    {templates.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay plantillas disponibles. Crea una plantilla
                            primero.
                        </p>
                    ) : (
                        <div className="space-y-4">
                            <Field>
                                <FieldLabel>Plantilla</FieldLabel>

                                <Select
                                    items={templates.map((template) => ({
                                        value: String(template.id),
                                        label: template.name,
                                    }))}
                                    value={
                                        templateId === null
                                            ? ''
                                            : String(templateId)
                                    }
                                    onValueChange={changeTemplate}
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Seleccionar plantilla" />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectGroup>
                                            {templates.map((template) => (
                                                <SelectItem
                                                    key={template.id}
                                                    value={String(template.id)}
                                                >
                                                    {template.name}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </Field>

                            <Field>
                                <FieldLabel>Versión</FieldLabel>

                                <Select
                                    items={versions.map((value) => ({
                                        value: String(value),
                                        label: `v${value}`,
                                    }))}
                                    value={
                                        version === null ? '' : String(version)
                                    }
                                    onValueChange={(value) =>
                                        setVersion(
                                            value === null
                                                ? null
                                                : Number(value),
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue placeholder="Seleccionar versión" />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectGroup>
                                            {versions.map((value) => (
                                                <SelectItem
                                                    key={value}
                                                    value={String(value)}
                                                >
                                                    v{value}
                                                </SelectItem>
                                            ))}
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </Field>

                            <div className="flex justify-end">
                                <Button
                                    type="button"
                                    disabled={
                                        templateId === null || version === null
                                    }
                                    onClick={continueToForm}
                                >
                                    Continuar <ArrowRight />
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </div>
    );
}
