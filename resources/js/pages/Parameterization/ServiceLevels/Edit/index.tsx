import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import serviceLevels from '@/routes/parameterization/service-levels';
import type { ServiceLevel } from '@/types/maintenance/maintenance';

import { ServiceLevelForm } from '../partials/form/service-level-form';

interface CategoryOption {
    id: number;
    name: string;
    children?: CategoryOption[];
}

interface EnumOption {
    value: string;
    label: string;
}

interface Props {
    action: string;
    service_level: ServiceLevel;
    maintenance_categories: CategoryOption[];
    maintenance_types: EnumOption[];
    priorities: EnumOption[];
}

export default function ServiceLevelsEdit({
    action,
    service_level,
    maintenance_categories,
    maintenance_types,
    priorities,
}: Props) {
    return (
        <div className="w-full py-10">
            <Link
                href={serviceLevels.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a niveles de servicio
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Editar nivel de servicio
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Ajusta las dimensiones y los tiempos esperados de este nivel
                    de servicio.
                </p>
            </div>

            <ServiceLevelForm
                method="put"
                action={action}
                maintenanceCategories={maintenance_categories}
                maintenanceTypes={maintenance_types}
                priorities={priorities}
                initialData={service_level}
            />
        </div>
    );
}
