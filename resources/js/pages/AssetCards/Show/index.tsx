import { Link } from '@inertiajs/react';
import { ArrowLeft, Pencil } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import type {
    AssetCardSummary,
    AssetFieldValue,
    AssetFormValues,
    AssetTemplateSummary,
} from '@/types/assetCards/assetCard';
import type {
    AssetCardTemplateSection,
    TemplateField,
} from '@/types/assetCardTemplates/assetCardTemplate';

interface Props {
    assetCard: AssetCardSummary;
    template: AssetTemplateSummary;
    sections: AssetCardTemplateSection[];
    values: AssetFormValues;
    editUrl: string;
    indexUrl: string;
}

function formatValue(field: TemplateField, value: AssetFieldValue): string {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (field.type === 'checkbox') {
        return value === true ? 'Sí' : 'No';
    }

    if (field.type === 'select' || field.type === 'radio') {
        const option = (field.options ?? []).find(
            (item) => item.value === value,
        );

        return option?.label ?? String(value);
    }

    return String(value);
}

export default function AssetCardsShow({
    assetCard,
    template,
    sections,
    values,
    editUrl,
    indexUrl,
}: Props) {
    return (
        <div className="container mx-auto max-w-5xl py-10">
            <Link
                href={indexUrl}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a fichas
            </Link>

            <div className="mb-8 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {assetCard.code}
                        </h1>

                        <Badge variant="outline">v{template.version}</Badge>
                    </div>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Plantilla:{' '}
                        <span className="font-medium">{template.name}</span>
                    </p>
                </div>

                <Button render={<Link href={editUrl} />}>
                    <Pencil /> Editar
                </Button>
            </div>

            <div className="grid grid-cols-12 gap-4">
                {sections.map((section) => {
                    const columns = Math.min(Math.max(section.columns, 1), 12);
                    const fields = [...section.fields].sort(
                        (first, second) => first.order - second.order,
                    );

                    return (
                        <div
                            key={section.id}
                            style={{
                                gridColumn: `span ${columns} / span ${columns}`,
                            }}
                        >
                            <Card className="h-full">
                                <CardHeader>
                                    <CardTitle>{section.name}</CardTitle>

                                    {section.description && (
                                        <CardDescription>
                                            {section.description}
                                        </CardDescription>
                                    )}
                                </CardHeader>

                                <CardContent>
                                    <dl className="space-y-3">
                                        {fields.map((field) => (
                                            <div
                                                key={field.name}
                                                className="flex flex-col gap-0.5"
                                            >
                                                <dt className="text-xs font-medium text-muted-foreground">
                                                    {field.label}
                                                </dt>

                                                <dd className="text-sm">
                                                    {formatValue(
                                                        field,
                                                        values[section.id]?.[
                                                            field.name
                                                        ] ?? null,
                                                    )}
                                                </dd>
                                            </div>
                                        ))}
                                    </dl>
                                </CardContent>
                            </Card>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
