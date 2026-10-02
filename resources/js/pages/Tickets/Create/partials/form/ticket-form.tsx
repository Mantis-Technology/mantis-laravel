import { Form } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import type { ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface AssetCardOption {
    id: number;
    code: string;
}

interface LocationOption {
    id: number;
    name: string;
    children?: LocationOption[];
}

interface TicketFormProps {
    action: string;
    assetCard: AssetCardOption | null;
    assetCards: AssetCardOption[];
    locations: LocationOption[];
}

function renderLocationOptions(
    locations: LocationOption[],
    depth: number,
): ReactNode[] {
    return locations.flatMap((location) => [
        <option key={location.id} value={location.id}>
            {'\u00A0\u00A0'.repeat(depth)}
            {location.name}
        </option>,
        ...(location.children?.length
            ? renderLocationOptions(location.children, depth + 1)
            : []),
    ]);
}

export function TicketForm({
    action,
    assetCard,
    assetCards,
    locations,
}: TicketFormProps) {
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
                        <FieldLabel htmlFor="asset_card_id">
                            Activo afectado
                        </FieldLabel>

                        {assetCard ? (
                            <>
                                <input
                                    type="hidden"
                                    name="asset_card_id"
                                    value={assetCard.id}
                                />

                                <Input
                                    id="asset_card_id"
                                    value={assetCard.code}
                                    disabled
                                    aria-invalid={!!errors.asset_card_id}
                                />
                            </>
                        ) : (
                            <select
                                id="asset_card_id"
                                name="asset_card_id"
                                defaultValue=""
                                disabled={processing}
                                className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                aria-invalid={!!errors.asset_card_id}
                            >
                                <option value="">
                                    Selecciona un activo...
                                </option>

                                {assetCards.map((option) => (
                                    <option key={option.id} value={option.id}>
                                        {option.code}
                                    </option>
                                ))}
                            </select>
                        )}

                        <FieldError>{errors.asset_card_id}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="title">Título</FieldLabel>

                        <Input
                            id="title"
                            name="title"
                            placeholder="Ej. El equipo no enciende"
                            disabled={processing}
                            aria-invalid={!!errors.title}
                        />

                        <FieldError>{errors.title}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="description">
                            Descripción del problema
                        </FieldLabel>

                        <Textarea
                            id="description"
                            name="description"
                            placeholder="Describe qué ocurrió, desde cuándo y cualquier contexto relevante..."
                            rows={5}
                            disabled={processing}
                            aria-invalid={!!errors.description}
                        />

                        <FieldError>{errors.description}</FieldError>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="location_id">
                            Ubicación (opcional)
                        </FieldLabel>

                        <select
                            id="location_id"
                            name="location_id"
                            defaultValue=""
                            disabled={processing}
                            className="flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm shadow-xs ring-offset-background transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            aria-invalid={!!errors.location_id}
                        >
                            <option value="">Sin ubicación</option>

                            {renderLocationOptions(locations, 0)}
                        </select>

                        <FieldError>{errors.location_id}</FieldError>
                    </Field>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Reportar incidencia
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
