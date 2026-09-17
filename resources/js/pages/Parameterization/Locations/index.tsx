import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';

import type { Location } from '@/types/locations/location';
import { buildColumns } from './columns';
import { DataTable } from './data-table';

interface Props {
    [key: string]: unknown;
    locations: Location[];
    can_toggle_active: boolean;
}

export default function LocationsIndex() {
    const { locations, can_toggle_active } = usePage<Props>().props;

    return (
        <div className="w-full py-10">
            <div className="mb-8 flex items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Sedes y ubicaciones
                    </h1>

                    <p className="mt-2 text-sm text-muted-foreground">
                        Administra las sedes y áreas donde se encuentran los
                        activos de la empresa.
                    </p>
                </div>

                <Link
                    href="/parameterization/locations/create"
                    className="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Plus className="h-4 w-4" />
                    Nueva ubicación
                </Link>
            </div>

            <DataTable
                columns={buildColumns(can_toggle_active)}
                data={locations}
                getSubRows={(row) => row.children}
            />
        </div>
    );
}