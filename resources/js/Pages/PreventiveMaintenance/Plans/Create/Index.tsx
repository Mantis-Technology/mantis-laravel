import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import preventivePlansRoutes from '@/routes/preventive-plans';
import type {
    PreventiveAssetCardRef,
    PreventiveCategoryRef,
    PreventiveUser,
} from '@/Types/PreventiveMaintenance/Common';
import type { FrequencyOption } from '@/Types/PreventiveMaintenance/Plan';

import { PlanCreateForm } from './Partials/Plan-Create-Form';

interface Props {
    [key: string]: unknown;
    asset_cards: PreventiveAssetCardRef[];
    maintenance_categories: PreventiveCategoryRef[];
    technicians: PreventiveUser[];
    frequencies: FrequencyOption[];
    action: string;
}

export default function PreventivePlansCreate({
    asset_cards,
    maintenance_categories,
    technicians,
    frequencies,
    action,
}: Props) {
    return (
        <div className="w-full py-10">
            <Head title="Nuevo plan preventivo" />

            <Link
                href={preventivePlansRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a planes preventivos
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nuevo plan preventivo
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Programa un mantenimiento que se repite por tiempo. Las
                    ejecuciones se generan automáticamente para los próximos 90
                    días.
                </p>
            </div>

            <PlanCreateForm
                action={action}
                assetCards={asset_cards}
                categories={maintenance_categories}
                technicians={technicians}
                frequencies={frequencies}
            />
        </div>
    );
}
