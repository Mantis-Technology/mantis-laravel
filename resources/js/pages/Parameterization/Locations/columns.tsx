import { Link, router } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { ChevronDown, ChevronRight } from 'lucide-react';
import { useState } from 'react';

import { Switch } from '@/components/ui/switch';
import locations from '@/routes/parameterization/locations';

import type { Location } from '@/types/locations/location';
import type { DataTableFeatures } from './data-table-features';

const columnHelper = createColumnHelper<DataTableFeatures, Location>();

const formatDate = (date: string | null) => {
    if (!date) {
        return '-';
    }

    return new Intl.DateTimeFormat('es-CO', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(date));
};

function ActiveToggle({ location }: { location: Location }) {
    const [processing, setProcessing] = useState(false);

    const handleChange = () => {
        setProcessing(true);

        router.patch(
            locations.toggleActive.url(location.id),
            {},
            {
                preserveScroll: true,
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <Switch
            checked={location.is_active}
            onCheckedChange={handleChange}
            disabled={processing}
            aria-label={
                location.is_active
                    ? `Desactivar ${location.name}`
                    : `Activar ${location.name}`
            }
        />
    );
}

export function buildColumns(canToggleActive: boolean) {
    return columnHelper.columns([
        columnHelper.accessor('name', {
            header: 'Nombre',

            cell: (info) => {
                const row = info.row;
                const location = row.original;

                return (
                    <div
                        className="flex items-center gap-2"
                        style={{
                            paddingLeft: `${row.depth * 24}px`,
                        }}
                    >
                        {row.getCanExpand() ? (
                            <button
                                type="button"
                                onClick={row.getToggleExpandedHandler()}
                                className="flex size-6 shrink-0 items-center justify-center rounded-md hover:bg-muted"
                                aria-label={
                                    row.getIsExpanded()
                                        ? 'Contraer ubicación'
                                        : 'Expandir ubicación'
                                }
                            >
                                {row.getIsExpanded() ? (
                                    <ChevronDown className="size-4" />
                                ) : (
                                    <ChevronRight className="size-4" />
                                )}
                            </button>
                        ) : (
                            <span className="size-6 shrink-0" />
                        )}

                        <Link
                            href={`/parameterization/locations/${location.id}/edit`}
                            className="font-medium hover:underline"
                        >
                            {location.name}
                        </Link>
                    </div>
                );
            },
        }),

        columnHelper.accessor('description', {
            header: 'Descripción',

            cell: (info) => (
                <span className="text-muted-foreground">
                    {info.getValue() || '-'}
                </span>
            ),
        }),

        columnHelper.accessor('is_active', {
            header: 'Estado',

            cell: (info) =>
                canToggleActive ? (
                    <ActiveToggle location={info.row.original} />
                ) : (
                    <span className="text-muted-foreground">
                        {info.row.original.is_active ? 'Activa' : 'Inactiva'}
                    </span>
                ),
        }),

        columnHelper.accessor('created_at', {
            header: 'Creado',

            cell: (info) => formatDate(info.getValue()),
        }),

        columnHelper.accessor('updated_at', {
            header: 'Actualizado',

            cell: (info) => formatDate(info.getValue()),
        }),
    ]);
}