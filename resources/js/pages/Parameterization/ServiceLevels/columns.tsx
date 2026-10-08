import { Link, router } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { useState } from 'react';

import { Switch } from '@/components/ui/switch';
import serviceLevels from '@/routes/parameterization/service-levels';
import type { ServiceLevel } from '@/types/maintenance/maintenance';

import type { DataTableFeatures } from './data-table-features';

const columnHelper = createColumnHelper<DataTableFeatures, ServiceLevel>();

const formatDate = (date: string | null) => {
    if (!date) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

const formatHours = (hours: number) => {
    if (hours < 24) {
        return `${hours} h`;
    }

    const days = Math.floor(hours / 24);
    const remaining = hours % 24;

    return remaining === 0 ? `${days} d` : `${days} d ${remaining} h`;
};

function ActiveToggle({ level }: { level: ServiceLevel }) {
    const [processing, setProcessing] = useState(false);

    const handleChange = () => {
        setProcessing(true);

        router.patch(
            serviceLevels.toggleActive.url(level.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Switch
            checked={level.is_active}
            onCheckedChange={handleChange}
            disabled={processing}
            aria-label={
                level.is_active
                    ? `Desactivar nivel ${level.id}`
                    : `Activar nivel ${level.id}`
            }
        />
    );
}

export function buildColumns(canToggleActive: boolean) {
    return columnHelper.columns([
        columnHelper.accessor('maintenance_category', {
            header: 'Categoría',

            cell: (info) => {
                const level = info.row.original;

                return (
                    <Link
                        href={`/parameterization/service-levels/${level.id}/edit`}
                        className="font-medium hover:underline"
                    >
                        {level.maintenance_category?.name ?? 'Todas'}
                    </Link>
                );
            },
        }),

        columnHelper.accessor('maintenance_type_label', {
            header: 'Tipo',

            cell: (info) => (
                <span className="text-muted-foreground">
                    {info.getValue() ?? 'Todos'}
                </span>
            ),
        }),

        columnHelper.accessor('priority_label', {
            header: 'Prioridad',

            cell: (info) => (
                <span className="text-muted-foreground">
                    {info.getValue() ?? 'Todas'}
                </span>
            ),
        }),

        columnHelper.accessor('response_hours', {
            header: 'Respuesta',

            cell: (info) => formatHours(info.getValue()),
        }),

        columnHelper.accessor('resolution_hours', {
            header: 'Resolución',

            cell: (info) => formatHours(info.getValue()),
        }),

        columnHelper.accessor('is_active', {
            header: 'Estado',

            cell: (info) =>
                canToggleActive ? (
                    <ActiveToggle level={info.row.original} />
                ) : (
                    <span className="text-muted-foreground">
                        {info.row.original.is_active ? 'Activo' : 'Inactivo'}
                    </span>
                ),
        }),

        columnHelper.accessor('created_at', {
            header: 'Creado',

            cell: (info) => formatDate(info.getValue()),
        }),
    ]);
}
