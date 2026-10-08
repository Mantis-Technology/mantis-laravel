import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import serviceLevels from '@/routes/parameterization/service-levels';

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
    maintenance_categories: CategoryOption[];
    maintenance_types: EnumOption[];
    priorities: EnumOption[];
}

export default function ServiceLevelsCreate({
    action,
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
                    Nuevo nivel de servicio
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Define los tiempos esperados de respuesta y resolución. Las
                    dimensiones que dejes vacías funcionan como comodín.
                </p>
            </div>

            <ServiceLevelForm
                method="post"
                action={action}
                maintenanceCategories={maintenance_categories}
                maintenanceTypes={maintenance_types}
                priorities={priorities}
            />
        </div>
    );
}
