import { Form } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import { Button } from '@/Components/Ui/Button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/Components/Ui/Field';
import { Input } from '@/Components/Ui/Input';
import { Textarea } from '@/Components/Ui/Textarea';
import { SELECT_CLASS } from '@/Lib/Form-Classes';
import { todayLocalIso } from '@/Lib/Preventive-Date';
import type {
    PreventiveAssetCardRef,
    PreventiveCategoryRef,
    PreventiveUser,
} from '@/Types/PreventiveMaintenance/Common';

interface Props {
    action: string;
    assetCards: PreventiveAssetCardRef[];
    categories: PreventiveCategoryRef[];
    technicians: PreventiveUser[];
}

export function ExecutionCreateForm({
    action,
    assetCards,
    categories,
    technicians,
}: Props) {
    return (
        <Form
            action={action}
            method="post"
            disableWhileProcessing
            className="space-y-6"
        >
            {({ errors, processing }) => (
                <FieldGroup>
                    <Field>
                        <FieldLabel htmlFor="asset_card_id">Activo</FieldLabel>

                        <select
                            id="asset_card_id"
                            name="asset_card_id"
                            defaultValue=""
                            disabled={processing}
                            className={SELECT_CLASS}
                            aria-invalid={!!errors.asset_card_id}
                        >
                            <option value="">Selecciona un activo...</option>

                            {assetCards.map((option) => (
                                <option key={option.id} value={option.id}>
                                    {option.code}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.asset_card_id}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="title">Título</FieldLabel>

                        <Input
                            id="title"
                            name="title"
                            placeholder="Ej. Revisión de filtros"
                            disabled={processing}
                            aria-invalid={!!errors.title}
                        />

                        <FieldError>{errors.title}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="description">
                            Descripción (opcional)
                        </FieldLabel>

                        <Textarea
                            id="description"
                            name="description"
                            rows={3}
                            disabled={processing}
                            aria-invalid={!!errors.description}
                        />

                        <FieldError>{errors.description}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="instructions">
                            Instrucciones (opcional)
                        </FieldLabel>

                        <Textarea
                            id="instructions"
                            name="instructions"
                            rows={4}
                            disabled={processing}
                            aria-invalid={!!errors.instructions}
                        />

                        <FieldError>{errors.instructions}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="maintenance_category_id">
                            Categoría
                        </FieldLabel>

                        <select
                            id="maintenance_category_id"
                            name="maintenance_category_id"
                            defaultValue=""
                            disabled={processing}
                            className={SELECT_CLASS}
                            aria-invalid={!!errors.maintenance_category_id}
                        >
                            <option value="">Selecciona una categoría...</option>

                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.maintenance_category_id}</FieldError>
                    </Field>

                    <div className="grid gap-6 sm:grid-cols-2">
                        <Field>
                            <FieldLabel htmlFor="scheduled_at">
                                Fecha programada
                            </FieldLabel>

                            <Input
                                id="scheduled_at"
                                name="scheduled_at"
                                type="date"
                                min={todayLocalIso()}
                                disabled={processing}
                                aria-invalid={!!errors.scheduled_at}
                            />

                            <FieldError>{errors.scheduled_at}</FieldError>
                        </Field>

                        <Field>
                            <FieldLabel htmlFor="assigned_to_id">
                                Técnico responsable
                            </FieldLabel>

                            <select
                                id="assigned_to_id"
                                name="assigned_to_id"
                                defaultValue=""
                                disabled={processing}
                                className={SELECT_CLASS}
                                aria-invalid={!!errors.assigned_to_id}
                            >
                                <option value="">
                                    Selecciona un técnico...
                                </option>

                                {technicians.map((technician) => (
                                    <option
                                        key={technician.id}
                                        value={technician.id}
                                    >
                                        {technician.name}
                                    </option>
                                ))}
                            </select>

                            <FieldError>{errors.assigned_to_id}</FieldError>
                        </Field>
                    </div>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Programar
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
