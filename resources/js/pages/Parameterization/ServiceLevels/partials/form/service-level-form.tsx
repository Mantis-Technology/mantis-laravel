import { Form } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';

import type { ServiceLevel } from '@/types/maintenance/maintenance';

interface CategoryOption {
    id: number;
    name: string;
    children?: CategoryOption[];
}

interface EnumOption {
    value: string;
    label: string;
}

interface ServiceLevelFormProps {
    method: 'post' | 'put';
    action: string;
    maintenanceCategories: CategoryOption[];
    maintenanceTypes: EnumOption[];
    priorities: EnumOption[];
    initialData?: ServiceLevel | null;
}

function renderCategoryOptions(
    categories: CategoryOption[],
    depth: number,
): ReactNode[] {
    return categories.flatMap((category) => [
        <option key={category.id} value={category.id}>
            {'\u00A0\u00A0'.repeat(depth)}
            {category.name}
        </option>,
        ...(category.children?.length
            ? renderCategoryOptions(category.children, depth + 1)
            : []),
    ]);
}

export function ServiceLevelForm({
    method,
    action,
    maintenanceCategories,
    maintenanceTypes,
    priorities,
    initialData,
}: ServiceLevelFormProps) {
    const [isActive, setIsActive] = useState(initialData?.is_active ?? true);

    return (
        <Form
            action={action}
            method={method}
            disableWhileProcessing
            className="space-y-6"
        >
            {({ errors, processing }) => (
                <FieldGroup>
                    <Field>
                        <FieldLabel htmlFor="maintenance_category_id">
                            Categoría
                        </FieldLabel>

                        <select
                            id="maintenance_category_id"
                            name="maintenance_category_id"
                            defaultValue={
                                initialData?.maintenance_category_id ?? ''
                            }
                            disabled={processing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.maintenance_category_id}
                        >
                            <option value="">Todas las categorías</option>

                            {renderCategoryOptions(maintenanceCategories, 0)}
                        </select>

                        <FieldError>
                            {errors.maintenance_category_id}
                        </FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="maintenance_type">
                            Tipo de mantenimiento
                        </FieldLabel>

                        <select
                            id="maintenance_type"
                            name="maintenance_type"
                            defaultValue={initialData?.maintenance_type ?? ''}
                            disabled={processing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.maintenance_type}
                        >
                            <option value="">Todos los tipos</option>

                            {maintenanceTypes.map((type) => (
                                <option key={type.value} value={type.value}>
                                    {type.label}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.maintenance_type}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="priority">Prioridad</FieldLabel>

                        <select
                            id="priority"
                            name="priority"
                            defaultValue={initialData?.priority ?? ''}
                            disabled={processing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.priority}
                        >
                            <option value="">Todas las prioridades</option>

                            {priorities.map((level) => (
                                <option key={level.value} value={level.value}>
                                    {level.label}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.priority}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="response_hours">
                            Tiempo de respuesta (horas)
                        </FieldLabel>

                        <Input
                            id="response_hours"
                            name="response_hours"
                            type="number"
                            min={1}
                            max={8760}
                            defaultValue={initialData?.response_hours ?? ''}
                            placeholder="Ej. 4"
                            disabled={processing}
                            aria-invalid={!!errors.response_hours}
                        />

                        <FieldError>{errors.response_hours}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="resolution_hours">
                            Tiempo de resolución (horas)
                        </FieldLabel>

                        <Input
                            id="resolution_hours"
                            name="resolution_hours"
                            type="number"
                            min={1}
                            max={8760}
                            defaultValue={initialData?.resolution_hours ?? ''}
                            placeholder="Ej. 24"
                            disabled={processing}
                            aria-invalid={!!errors.resolution_hours}
                        />

                        <FieldError>{errors.resolution_hours}</FieldError>
                    </Field>

                    <Field orientation="horizontal">
                        <FieldLabel htmlFor="is_active">
                            Nivel de servicio activo
                        </FieldLabel>

                        <input
                            type="hidden"
                            name="is_active"
                            value={isActive ? '1' : '0'}
                        />

                        <Switch
                            id="is_active"
                            checked={isActive}
                            onCheckedChange={setIsActive}
                            disabled={processing}
                        />
                    </Field>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}

                            {method === 'post'
                                ? 'Crear nivel de servicio'
                                : 'Actualizar nivel de servicio'}
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
