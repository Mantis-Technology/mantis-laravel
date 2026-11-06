import { router } from '@inertiajs/react';
import { ListFilterIcon, RotateCcwIcon } from 'lucide-react';
import type { ChangeEvent, FormEvent } from 'react';
import { useState } from 'react';

import { Button } from '@/Components/Ui/Button';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/Ui/Popover';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import { dashboard } from '@/routes';
import type {
    StatisticsFilters,
    StatisticsOptions,
} from '@/Types/Statistics/Statistics';

const INPUT_CLASS =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none';

const EMPTY_FILTERS: StatisticsFilters = {
    date_from: null,
    date_to: null,
    maintenance_category_id: null,
    asset_card_id: null,
    technician_id: null,
    status: null,
    preventive_status: null,
};

const buildParams = (filters: StatisticsFilters): Record<string, string> => {
    const params: Record<string, string> = {};

    if (filters.date_from) {
        params.date_from = filters.date_from;
    }

    if (filters.date_to) {
        params.date_to = filters.date_to;
    }

    if (filters.maintenance_category_id !== null) {
        params.maintenance_category_id = String(
            filters.maintenance_category_id,
        );
    }

    if (filters.asset_card_id !== null) {
        params.asset_card_id = String(filters.asset_card_id);
    }

    if (filters.technician_id !== null) {
        params.technician_id = String(filters.technician_id);
    }

    if (filters.status) {
        params.status = filters.status;
    }

    if (filters.preventive_status) {
        params.preventive_status = filters.preventive_status;
    }

    return params;
};

interface Props {
    filters: StatisticsFilters;
    options: StatisticsOptions;
}

export function StatisticsFiltersPopover({ filters, options }: Props) {
    const [draft, setDraft] = useState<StatisticsFilters>(filters);
    const [open, setOpen] = useState(false);

    const activeCount = Object.values(filters).filter(
        (value) => value !== null && value !== '',
    ).length;

    const changeOpen = (nextOpen: boolean) => {
        if (nextOpen) {
            setDraft(filters);
        }

        setOpen(nextOpen);
    };

    const update = <Key extends keyof StatisticsFilters>(
        key: Key,
        value: StatisticsFilters[Key],
    ) => {
        setDraft((current) => ({ ...current, [key]: value }));
    };

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        router.get(dashboard.url(), buildParams(draft), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        });
    };

    const clear = () => {
        setDraft(EMPTY_FILTERS);

        router.get(
            dashboard.url(),
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => setOpen(false),
            },
        );
    };

    return (
        <Popover open={open} onOpenChange={changeOpen}>
            <PopoverTrigger render={<Button variant="outline" />}>
                <ListFilterIcon />
                Filtros
                {activeCount > 0 && (
                    <span className="ml-1 inline-flex size-5 items-center justify-center rounded-full bg-primary text-[0.7rem] font-semibold text-primary-foreground">
                        {activeCount}
                    </span>
                )}
            </PopoverTrigger>

            <PopoverContent className="w-[min(40rem,calc(100vw-2rem))] p-5">
                <div className="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <h2 className="font-heading text-base font-medium text-foreground">
                            Filtrar estadísticas
                        </h2>

                        <p className="text-sm text-muted-foreground">
                            Acota la información por fechas, categoría, activo,
                            técnico y estado.
                        </p>
                    </div>

                    {activeCount > 0 && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={clear}
                        >
                            <RotateCcwIcon />
                            Limpiar
                        </Button>
                    )}
                </div>

                <form className="grid gap-4 sm:grid-cols-2" onSubmit={submit}>
                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Desde</span>

                        <input
                            type="date"
                            value={draft.date_from ?? ''}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                update('date_from', event.target.value || null)
                            }
                            className={INPUT_CLASS}
                        />
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Hasta</span>

                        <input
                            type="date"
                            value={draft.date_to ?? ''}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                update('date_to', event.target.value || null)
                            }
                            className={INPUT_CLASS}
                        />
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Categoría</span>

                        <select
                            value={draft.maintenance_category_id ?? ''}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                update(
                                    'maintenance_category_id',
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                                )
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todas las categorías</option>

                            {options.maintenance_categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Activo</span>

                        <select
                            value={draft.asset_card_id ?? ''}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                update(
                                    'asset_card_id',
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                                )
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los activos</option>

                            {options.asset_cards.map((assetCard) => (
                                <option key={assetCard.id} value={assetCard.id}>
                                    {assetCard.code}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Técnico</span>

                        <select
                            value={draft.technician_id ?? ''}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                update(
                                    'technician_id',
                                    event.target.value === ''
                                        ? null
                                        : Number(event.target.value),
                                )
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los técnicos</option>

                            {options.technicians.map((technician) => (
                                <option
                                    key={technician.id}
                                    value={technician.id}
                                >
                                    {technician.name}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Estado del caso</span>

                        <select
                            value={draft.status ?? ''}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                update(
                                    'status',
                                    event.target.value === ''
                                        ? null
                                        : event.target.value,
                                )
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los estados</option>

                            {options.ticket_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <label className="space-y-1.5 text-sm font-medium">
                        <span>Estado del preventivo</span>

                        <select
                            value={draft.preventive_status ?? ''}
                            onChange={(event: ChangeEvent<HTMLSelectElement>) =>
                                update(
                                    'preventive_status',
                                    event.target.value === ''
                                        ? null
                                        : event.target.value,
                                )
                            }
                            className={SELECT_CLASS}
                        >
                            <option value="">Todos los estados</option>

                            {options.preventive_statuses.map((status) => (
                                <option key={status.value} value={status.value}>
                                    {status.label}
                                </option>
                            ))}
                        </select>
                    </label>

                    <div className="flex items-end justify-end gap-2 sm:col-span-2">
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>

                        <Button type="submit">Aplicar filtros</Button>
                    </div>
                </form>
            </PopoverContent>
        </Popover>
    );
}
