import { Link } from '@inertiajs/react';
import { ArrowLeft, Download, Pencil, QrCode } from 'lucide-react';

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
    AssetFileUrls,
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
    fileUrls: AssetFileUrls;
    qrUrl: string | null;
    qrDownloadUrl: string | null;
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
    fileUrls,
    qrUrl,
    qrDownloadUrl,
    editUrl,
    indexUrl,
}: Props) {
    return (
        <div className="w-full py-10">
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

            {qrUrl && (
                <Card className="mb-4">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <QrCode className="size-4" /> Código QR
                        </CardTitle>

                        <CardDescription>
                            Escanealo para abrir esta ficha.
                        </CardDescription>
                    </CardHeader>

                    <CardContent className="flex flex-wrap items-center gap-4">
                        <img
                            src={qrUrl}
                            alt={`Código QR de ${assetCard.code}`}
                            className="size-32 rounded-lg border bg-white p-1"
                        />

                        <Button
                            variant="outline"
                            render={
                                <a
                                    href={qrDownloadUrl ?? qrUrl}
                                    download={`${assetCard.code}-qr.svg`}
                                />
                            }
                        >
                            <Download /> Descargar QR
                        </Button>
                    </CardContent>
                </Card>
            )}

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
                                        {fields.map((field) => {
                                            const value =
                                                values[section.id]?.[
                                                    field.name
                                                ] ?? null;
                                            const fileUrl =
                                                fileUrls[section.id]?.[
                                                    field.name
                                                ];

                                            return (
                                                <div
                                                    key={field.name}
                                                    className="flex flex-col gap-0.5"
                                                >
                                                    <dt className="text-xs font-medium text-muted-foreground">
                                                        {field.label}
                                                    </dt>

                                                    <dd className="text-sm">
                                                        {field.type ===
                                                            'file' &&
                                                        fileUrl ? (
                                                            <a
                                                                href={fileUrl}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="underline"
                                                            >
                                                                Ver archivo
                                                            </a>
                                                        ) : (
                                                            formatValue(
                                                                field,
                                                                value,
                                                            )
                                                        )}
                                                    </dd>
                                                </div>
                                            );
                                        })}
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
