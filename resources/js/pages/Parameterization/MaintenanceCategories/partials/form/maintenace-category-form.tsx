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
import { Textarea } from '@/components/ui/textarea';

import type { MaintenanceCategory } from '@/types/maintenance/maintenance';

interface ParentCategory {
    id: number;
    name: string;
    parent_id: number | null;
    children?: ParentCategory[];
}

interface MaintenanceCategoryFormProps {
    method: 'post' | 'put';
    action: string;
    parentCategories: ParentCategory[];
    disabledParentIds: number[];
    initialData?: MaintenanceCategory;
}

function renderCategoryOptions(
    categories: ParentCategory[],
    disabledParentIds: Set<number>,
    depth: number,
): ReactNode[] {
    return categories.flatMap((category) => [
        <option
            key={category.id}
            value={category.id}
            disabled={disabledParentIds.has(category.id)}
        >
            {'\u00A0\u00A0'.repeat(depth)}
            {category.name}
        </option>,
        ...(category.children?.length
            ? renderCategoryOptions(
                  category.children,
                  disabledParentIds,
                  depth + 1,
              )
            : []),
    ]);
}

export function MaintenanceCategoryForm({
    method,
    action,
    parentCategories,
    disabledParentIds,
    initialData,
}: MaintenanceCategoryFormProps) {
    const disabledParentIdsSet = new Set(disabledParentIds);
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
                        <FieldLabel htmlFor="parent_id">
                            Categoría superior
                        </FieldLabel>

                        <select
                            id="parent_id"
                            name="parent_id"
                            defaultValue={initialData?.parent_id ?? ''}
                            disabled={processing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.parent_id}
                        >
                            <option value="">Sin categoría superior</option>

                            {renderCategoryOptions(
                                parentCategories,
                                disabledParentIdsSet,
                                0,
                            )}
                        </select>

                        <FieldError>{errors.parent_id}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="name">Nombre</FieldLabel>

                        <Input
                            id="name"
                            name="name"
                            defaultValue={initialData?.name ?? ''}
                            placeholder="Ej. Computadores"
                            disabled={processing}
                            aria-invalid={!!errors.name}
                        />

                        <FieldError>{errors.name}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="description">
                            Descripción
                        </FieldLabel>

                        <Textarea
                            id="description"
                            name="description"
                            defaultValue={initialData?.description ?? ''}
                            placeholder="Describe la categoría..."
                            rows={4}
                            disabled={processing}
                            aria-invalid={!!errors.description}
                        />

                        <FieldError>{errors.description}</FieldError>
                    </Field>

                    <Field orientation="horizontal">
                        <FieldLabel htmlFor="is_active">
                            Categoría activa
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
                                ? 'Crear categoría'
                                : 'Actualizar categoría'}
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
