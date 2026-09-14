import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { FieldPreview } from '@/components/asset-card-templates/field-preview';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { FieldGroup } from '@/components/ui/field';

import type {
    AssetCardTemplate,
    AssetCardTemplateSection,
} from '@/types/assetCardTemplates/assetCardTemplate';

interface Props {
    template: AssetCardTemplate;
    sections: AssetCardTemplateSection[];
    builderUrl: string;
}

export default function AssetCardTemplatePreview({
    template,
    sections,
    builderUrl,
}: Props) {
    return (
        <div className="container mx-auto max-w-5xl py-10">
            <Link
                href={builderUrl}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver al builder
            </Link>

            <div className="mb-8">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {template.name}
                    </h1>

                    <Badge variant="outline">v{template.version}</Badge>
                </div>

                <p className="mt-2 text-sm text-muted-foreground">
                    {template.description ??
                        'Vista previa del formulario con las secciones y campos definidos.'}
                </p>
            </div>

            {sections.length === 0 ? (
                <Card>
                    <CardContent className="py-12 text-center text-sm text-muted-foreground">
                        Esta versión todavía no tiene secciones.
                    </CardContent>
                </Card>
            ) : (
                <div className="grid grid-cols-12 gap-4">
                    {sections.map((section) => {
                        const columns = Math.min(
                            Math.max(section.columns, 1),
                            12,
                        );
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
                                        {fields.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                Sin campos.
                                            </p>
                                        ) : (
                                            <FieldGroup>
                                                {fields.map((field) => (
                                                    <FieldPreview
                                                        key={`${section.id}-${field.name}`}
                                                        field={field}
                                                    />
                                                ))}
                                            </FieldGroup>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}
