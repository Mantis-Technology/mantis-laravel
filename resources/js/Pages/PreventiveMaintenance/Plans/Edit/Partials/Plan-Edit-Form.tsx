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
import { formatPreventiveDate } from '@/Lib/Preventive-Date';
import type {
    PreventiveCategoryRef,
    PreventiveUser,
} from '@/Types/PreventiveMaintenance/Common';
import type { PlanDetail } from '@/Types/PreventiveMaintenance/Plan';

interface Props {
    action: string;
    plan: PlanDetail;
    categories: PreventiveCategoryRef[];
    technicians: PreventiveUser[];
}

export function PlanEditForm({ action, plan, categories, technicians }: Props) {
    const isOnce = plan.frequency.value === 'once';

    return (
        <Form
            action={action}
            method="put"
            disableWhileProcessing
            className="space-y-6"
        >
            {({ errors, processing }) => (
                <FieldGroup>
                    <div className="grid gap-6 rounded-md border bg-muted/30 p-4 text-sm sm:grid-cols-3">
                        <div>
                            <p className="text-muted-foreground">Activo</p>
                            <p className="font-medium">
                                {plan.asset_card?.code ?? '—'}
                            </p>
                        </div>

                        <div>
                            <p className="text-muted-foreground">Frecuencia</p>
                            <p className="font-medium">{plan.frequency.label}</p>
                        </div>

                        <div>
                            <p className="text-muted-foreground">Inicio</p>
                            <p className="font-medium">
                                {formatPreventiveDate(plan.starts_at)}
                            </p>
                        </div>
                    </div>

                    <Field>
                        <FieldLabel htmlFor="title">Título</FieldLabel>

                        <Input
                            id="title"
                            name="title"
                            defaultValue={plan.title}
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
                            defaultValue={plan.description ?? ''}
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
                            defaultValue={plan.instructions ?? ''}
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
                            defaultValue={plan.category?.id ?? ''}
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

                    <Field>
                        <FieldLabel htmlFor="responsible_id">
                            Técnico responsable
                        </FieldLabel>

                        <select
                            id="responsible_id"
                            name="responsible_id"
                            defaultValue={plan.responsible?.id ?? ''}
                            disabled={processing}
                            className={SELECT_CLASS}
                            aria-invalid={!!errors.responsible_id}
                        >
                            <option value="">Selecciona un técnico...</option>

                            {technicians.map((technician) => (
                                <option
                                    key={technician.id}
                                    value={technician.id}
                                >
                                    {technician.name}
                                </option>
                            ))}
                        </select>

                        <FieldError>{errors.responsible_id}</FieldError>
                    </Field>

                    {!isOnce && (
                        <Field>
                            <FieldLabel htmlFor="ends_at">
                                Fecha de fin (opcional)
                            </FieldLabel>

                            <Input
                                id="ends_at"
                                name="ends_at"
                                type="date"
                                min={plan.starts_at}
                                defaultValue={plan.ends_at ?? ''}
                                disabled={processing}
                                aria-invalid={!!errors.ends_at}
                            />

                            <FieldError>{errors.ends_at}</FieldError>
                        </Field>
                    )}

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Guardar cambios
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
