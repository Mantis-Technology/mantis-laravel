import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import type { AssetTemplateSummary } from '@/types/assetCards/assetCard';
import type { AssetCardTemplateSection } from '@/types/assetCardTemplates/assetCardTemplate';

import { AssetForm } from '../partials/asset-form';

interface Props {
    template: AssetTemplateSummary;
    sections: AssetCardTemplateSection[];
    action: string;
    cancelUrl: string;
}

export default function AssetCardsCreate({
    template,
    sections,
    action,
    cancelUrl,
}: Props) {
    return (
        <div className="w-full py-10">
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
                    <span className="font-medium">{template.name}</span> ·
                    versión {template.version}
                </p>
            </div>

            <AssetForm
                method="post"
                action={action}
                templateId={template.id}
                version={template.version}
                sections={sections}
                initialValues={{}}
                cancelUrl={cancelUrl}
            />
        </div>
    );
}
