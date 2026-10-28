import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import type {
    AssetCardSummary,
    AssetFileUrls,
    AssetFormValues,
    AssetTemplateSummary,
} from '@/Types/AssetCards/AssetCard';
import type { AssetCardTemplateSection } from '@/Types/AssetCardTemplates/AssetCardTemplate';

import { AssetForm } from '../Partials/Asset-Form';

interface Props {
    assetCard: AssetCardSummary;
    template: AssetTemplateSummary;
    sections: AssetCardTemplateSection[];
    values: AssetFormValues;
    fileUrls: AssetFileUrls;
    action: string;
    cancelUrl: string;
}

export default function AssetCardsEdit({
    assetCard,
    template,
    sections,
    values,
    fileUrls,
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
                Volver a la ficha
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Editar ficha {assetCard.code}
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Plantilla:{' '}
                    <span className="font-medium">{template.name}</span> ·
                    versión {template.version}
                </p>
            </div>

            <AssetForm
                method="put"
                action={action}
                templateId={template.id}
                version={template.version}
                sections={sections}
                initialValues={values}
                fileUrls={fileUrls}
                cancelUrl={cancelUrl}
            />
        </div>
    );
}
