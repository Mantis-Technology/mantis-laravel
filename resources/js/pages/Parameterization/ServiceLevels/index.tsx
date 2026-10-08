import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import type { ServiceLevel } from '@/types/maintenance/maintenance';

import { buildColumns } from './columns';
import { DataTable } from './data-table';

interface Props {
    [key: string]: unknown;
    service_levels: ServiceLevel[];
    can_toggle_active: boolean;
}

export default function ServiceLevelsIndex() {
    const { service_levels, can_toggle_active } = usePage<Props>().props;

    return (
        <div className="w-full py-10">
            <div className="mb-8 flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Niveles de servicio
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Define los tiempos esperados de respuesta y resolución
                        según la categoría, el tipo y la prioridad de cada caso.
                    </p>
                </div>

                <Link
                    href="/parameterization/service-levels/create"
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4" />
                    Nuevo nivel de servicio
                </Link>
            </div>

            <DataTable
                columns={buildColumns(can_toggle_active)}
                data={service_levels}
            />
        </div>
    );
}
