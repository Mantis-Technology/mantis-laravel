import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import preventiveExecutionsRoutes from '@/routes/preventive-executions';
import type {
    PreventiveAssetCardRef,
    PreventiveCategoryRef,
    PreventiveUser,
} from '@/Types/PreventiveMaintenance/Common';

import { ExecutionCreateForm } from './Partials/Execution-Create-Form';

interface Props {
    [key: string]: unknown;
    asset_cards: PreventiveAssetCardRef[];
    maintenance_categories: PreventiveCategoryRef[];
    technicians: PreventiveUser[];
    action: string;
}

export default function PreventiveExecutionsCreate({
    asset_cards,
    maintenance_categories,
    technicians,
    action,
}: Props) {
    return (
        <div className="w-full py-10">
            <Head title="Programar mantenimiento" />

            <Link
                href={preventiveExecutionsRoutes.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a ejecuciones
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Programar mantenimiento
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Programa un mantenimiento preventivo puntual que no
                    pertenece a ningún plan.
                </p>
            </div>

            <ExecutionCreateForm
                action={action}
                assetCards={asset_cards}
                categories={maintenance_categories}
                technicians={technicians}
            />
        </div>
    );
}
