import { Form } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

interface AssetCardTemplateFormProps {
    action: string;
}

export function AssetCardTemplateForm({ action }: AssetCardTemplateFormProps) {
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
                        <FieldLabel htmlFor="name">Nombre</FieldLabel>

                        <Input
                            id="name"
                            name="name"
                            placeholder="Ej. Equipo de cómputo"
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
                            placeholder="Descripción opcional de la plantilla"
                            disabled={processing}
                            aria-invalid={!!errors.description}
                        />

                        <FieldError>{errors.description}</FieldError>
                    </Field>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing && (
                                <LoaderCircle className="animate-spin" />
                            )}
                            Crear plantilla
                        </Button>
                    </div>
                </FieldGroup>
            )}
        </Form>
    );
}
