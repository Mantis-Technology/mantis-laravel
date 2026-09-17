import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import locations from '@/routes/parameterization/locations';
import type { Location } from '@/types/locations/location';

import { LocationForm } from '../partials/form/location-form';

interface ParentLocation {
    id: number;
    name: string;
    parent_id: number | null;
    children?: ParentLocation[];
}

interface Props {
    action: string;
    location: Location;
    parent_locations: ParentLocation[];
    disabled_parent_ids: number[];
}

export default function LocationsEdit({
    action,
    location,
    parent_locations,
    disabled_parent_ids,
}: Props) {
    return (
        <div className="w-full py-10">
            <Link
                href={locations.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a ubicaciones
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Editar ubicación
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Modifica la información de esta sede o área.
                </p>
            </div>

            <LocationForm
                method="put"
                action={action}
                parentLocations={parent_locations}
                disabledParentIds={disabled_parent_ids}
                initialData={location}
            />
        </div>
    );
}