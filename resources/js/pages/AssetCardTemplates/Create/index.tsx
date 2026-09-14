import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Card, CardContent } from '@/components/ui/card';

import { AssetCardTemplateForm } from './partials/form/asset-card-template-form';

interface Props {
    action: string;
    indexUrl: string;
}

export default function AssetCardTemplatesCreate({ action, indexUrl }: Props) {
    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={indexUrl}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a plantillas
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nueva plantilla
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Crea una plantilla de ficha de activo. Al crearla se
                    generará su primera versión y podrás añadir secciones y
                    campos.
                </p>
            </div>

            <Card>
                <CardContent className="space-y-6 pt-6">
                    <AssetCardTemplateForm action={action} />
                </CardContent>
            </Card>
        </div>
    );
}
