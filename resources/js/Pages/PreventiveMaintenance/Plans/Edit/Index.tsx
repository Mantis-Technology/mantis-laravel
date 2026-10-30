import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import preventivePlansRoutes from '@/routes/preventive-plans';
import type {
    PreventiveCategoryRef,
    PreventiveUser,
} from '@/Types/PreventiveMaintenance/Common';
import type { PlanDetail } from '@/Types/PreventiveMaintenance/Plan';

import { PlanEditForm } from './Partials/Plan-Edit-Form';

interface Props {
    [key: string]: unknown;
    plan: PlanDetail;
    maintenance_categories: PreventiveCategoryRef[];
    technicians: PreventiveUser[];
    action: string;
}

export default function PreventivePlansEdit({
    plan,
    maintenance_categories,
    technicians,
    action,
}: Props) {
    return (
        <div className="w-full py-10">
            <Head title={`Editar plan: ${plan.title}`} />

            <Link
                href={preventivePlansRoutes.show.url(plan.id)}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver al plan
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Editar plan preventivo
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    La frecuencia y la fecha de inicio no se pueden cambiar. Si
                    necesitas otro calendario, cancela este plan y crea uno
                    nuevo. Un nuevo responsable aplica a las ejecuciones que se
                    generen de ahora en adelante.
                </p>
            </div>

            <PlanEditForm
                action={action}
                plan={plan}
                categories={maintenance_categories}
                technicians={technicians}
            />
        </div>
    );
}
