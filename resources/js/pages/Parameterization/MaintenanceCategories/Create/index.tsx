import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import maintenanceCategories from '@/routes/parameterization/maintenance-categories';

import { MaintenanceCategoryForm } from '../partials/form/maintenace-category-form';

interface ParentCategory {
    id: number;
    name: string;
    parent_id: number | null;
    children?: ParentCategory[];
}

interface Props {
    action: string;
    parent_categories: ParentCategory[];
    disabled_parent_ids: number[];
}

export default function MaintenanceCategoriesCreate({
    action,
    parent_categories,
    disabled_parent_ids,
}: Props) {
    return (
        <div className="container mx-auto max-w-3xl py-10">
            <Link
                href={maintenanceCategories.index.url()}
                className="mb-6 inline-flex items-center gap-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a categorías
            </Link>

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight">
                    Nueva categoría
                </h1>

                <p className="mt-2 text-sm text-muted-foreground">
                    Crea una categoría para clasificar las solicitudes de
                    mantenimiento. Puedes asignarla a una categoría superior o
                    dejarla como categoría principal.
                </p>
            </div>

            <MaintenanceCategoryForm
                method="post"
                action={action}
                parentCategories={parent_categories}
                disabledParentIds={disabled_parent_ids}
            />
        </div>
    );
}
