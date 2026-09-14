import { Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Field, FieldLabel } from '@/components/ui/field';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import type {
    AssetTemplateOption,
    AssetTemplateSummary,
} from '@/types/assetCards/assetCard';
import type { AssetCardTemplateSection } from '@/types/assetCardTemplates/assetCardTemplate';

import { AssetForm } from '../partials/asset-form';

interface Props {
    templates: AssetTemplateOption[];
    selectedTemplate: AssetTemplateSummary | null;
    sections: AssetCardTemplateSection[];
    createUrl: string;
    action: string;
    cancelUrl: string;
}

export default function AssetCardsCreate({
    templates,
    selectedTemplate,
    sections,
    createUrl,
    action,
    cancelUrl,
}: Props) {
    const [templateId, setTemplateId] = useState<number | null>(
        templates[0]?.id ?? null,
    );
    const [version, setVersion] = useState<number | null>(
        templates[0]?.versions[0] ?? null,
    );

    const selectedOption =
        templates.find((template) => template.id === templateId) ?? null;
    const versions = selectedOption?.versions ?? [];

    function handleTemplateChange(value: string | null) {
        const template =
            templates.find((item) => String(item.id) === value) ?? null;

        setTemplateId(template?.id ?? null);
        setVersion(template?.versions[0] ?? null);
    }

    if (selectedTemplate !== null) {
        return (
            <div className="container mx-auto max-w-5xl py-10">
                <Link
                    href={cancelUrl}
                    className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Volver a fichas
                </Link>

                <div className="mb-8">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Nueva ficha de activo
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Plantilla:{' '}
                        <span className="font-medium">
                            {selectedTemplate.name}
                        </span>{' '}
                        · versión {selectedTemplate.version}
                    </p>
                </div>

                <AssetForm
                    method="post"
                    action={action}
                    templateId={selectedTemplate.id}
                    version={selectedTemplate.version}
                    sections={sections}
                    initialCode=""
                    initialValues={{}}
                    cancelUrl={cancelUrl}
                />
            </div>
        );
    }

    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={cancelUrl}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a fichas
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nueva ficha de activo
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Selecciona la plantilla y la versión para cargar su
                    formulario.
                </p>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Plantilla y versión</CardTitle>

                    <CardDescription>
                        El formulario se generará según las secciones de la
                        versión elegida.
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-4">
                    {templates.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No hay plantillas disponibles.{' '}
                            <Link
                                href="/asset-card-templates/create"
                                className="underline"
                            >
                                Crea una plantilla
                            </Link>{' '}
                            primero.
                        </p>
                    ) : (
                        <>
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
                                    onValueChange={handleTemplateChange}
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
                                    onClick={() => {
                                        if (
                                            templateId !== null &&
                                            version !== null
                                        ) {
                                            router.get(createUrl, {
                                                template_id: templateId,
                                                version,
                                            });
                                        }
                                    }}
                                >
                                    Continuar <ArrowRight />
                                </Button>
                            </div>
                        </>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
