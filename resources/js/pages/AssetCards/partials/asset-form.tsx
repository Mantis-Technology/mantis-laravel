import { Link, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import type { FormEvent } from 'react';

import { AssetFieldInput } from '@/components/asset-card-templates/asset-field-input';
import { AssetFileInput } from '@/components/asset-card-templates/asset-file-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

import type {
    AssetFieldValue,
    AssetFileRemovals,
    AssetFileUrls,
    AssetFileValues,
    AssetFormValues,
} from '@/types/assetCards/assetCard';
import type { AssetCardTemplateSection } from '@/types/assetCardTemplates/assetCardTemplate';

interface AssetFormProps {
    method: 'post' | 'put';
    action: string;
    templateId: number;
    version: number;
    sections: AssetCardTemplateSection[];
    initialCode: string;
    initialValues: AssetFormValues;
    fileUrls?: AssetFileUrls;
    cancelUrl: string;
}

export function AssetForm({
    method,
    action,
    templateId,
    version,
    sections,
    initialCode,
    initialValues,
    fileUrls = {},
    cancelUrl,
}: AssetFormProps) {
    const form = useForm({
        code: initialCode,
        template_id: templateId,
        version,
        values: initialValues,
        files: {} as AssetFileValues,
        remove_files: {} as AssetFileRemovals,
    });

    const errors = form.errors as Record<string, string | undefined>;

    function setFieldValue(
        sectionId: string,
        fieldName: string,
        value: AssetFieldValue,
    ) {
        form.setData('values', {
            ...form.data.values,
            [sectionId]: {
                ...(form.data.values[sectionId] ?? {}),
                [fieldName]: value,
            },
        });
    }

    function setRemoveFile(
        sectionId: string,
        fieldName: string,
        removed: boolean,
    ) {
        form.setData('remove_files', {
            ...form.data.remove_files,
            [sectionId]: {
                ...(form.data.remove_files[sectionId] ?? {}),
                [fieldName]: removed,
            },
        });
    }

    function setFieldFile(
        sectionId: string,
        fieldName: string,
        file: File | null,
    ) {
        form.setData('files', {
            ...form.data.files,
            [sectionId]: {
                ...(form.data.files[sectionId] ?? {}),
                [fieldName]: file,
            },
        });

        if (file !== null) {
            setRemoveFile(sectionId, fieldName, false);
        }
    }

    function toggleRemoveFile(sectionId: string, fieldName: string) {
        const removed = form.data.remove_files[sectionId]?.[fieldName] ?? false;

        setRemoveFile(sectionId, fieldName, !removed);

        if (!removed) {
            setFieldFile(sectionId, fieldName, null);
        }
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (method === 'put') {
            form.put(action, { preserveScroll: true });

            return;
        }

        form.post(action, { preserveScroll: true });
    }

    return (
        <form onSubmit={submit} className="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Código</CardTitle>

                    <CardDescription>
                        Identificador único de la ficha de activo.
                    </CardDescription>
                </CardHeader>

                <CardContent>
                    <Field data-invalid={!!form.errors.code}>
                        <FieldLabel htmlFor="code">Código</FieldLabel>

                        <Input
                            id="code"
                            value={form.data.code}
                            disabled={form.processing}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                        />

                        <FieldError>{form.errors.code}</FieldError>
                    </Field>
                </CardContent>
            </Card>

            <div className="grid grid-cols-12 gap-4">
                {sections.map((section) => {
                    const columns = Math.min(Math.max(section.columns, 1), 12);
                    const fields = [...section.fields].sort(
                        (first, second) => first.order - second.order,
                    );

                    return (
                        <div
                            key={section.id}
                            style={{
                                gridColumn: `span ${columns} / span ${columns}`,
                            }}
                        >
                            <Card className="h-full">
                                <CardHeader>
                                    <CardTitle>{section.name}</CardTitle>

                                    {section.description && (
                                        <CardDescription>
                                            {section.description}
                                        </CardDescription>
                                    )}
                                </CardHeader>

                                <CardContent>
                                    <FieldGroup>
                                        {fields.map((field) =>
                                            field.type === 'file' ? (
                                                <AssetFileInput
                                                    key={field.name}
                                                    field={field}
                                                    error={
                                                        errors[
                                                            `values.${section.id}.${field.name}`
                                                        ] ??
                                                        errors[
                                                            `files.${section.id}.${field.name}`
                                                        ]
                                                    }
                                                    existingUrl={
                                                        fileUrls[section.id]?.[
                                                            field.name
                                                        ]
                                                    }
                                                    removed={
                                                        form.data.remove_files[
                                                            section.id
                                                        ]?.[field.name] ?? false
                                                    }
                                                    disabled={form.processing}
                                                    onFileChange={(file) =>
                                                        setFieldFile(
                                                            section.id,
                                                            field.name,
                                                            file,
                                                        )
                                                    }
                                                    onToggleRemove={() =>
                                                        toggleRemoveFile(
                                                            section.id,
                                                            field.name,
                                                        )
                                                    }
                                                />
                                            ) : (
                                                <AssetFieldInput
                                                    key={field.name}
                                                    field={field}
                                                    value={
                                                        form.data.values[
                                                            section.id
                                                        ]?.[field.name] ?? null
                                                    }
                                                    error={
                                                        errors[
                                                            `values.${section.id}.${field.name}`
                                                        ]
                                                    }
                                                    disabled={form.processing}
                                                    onChange={(value) =>
                                                        setFieldValue(
                                                            section.id,
                                                            field.name,
                                                            value,
                                                        )
                                                    }
                                                />
                                            ),
                                        )}
                                    </FieldGroup>
                                </CardContent>
                            </Card>
                        </div>
                    );
                })}
            </div>

            <div className="flex justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    render={<Link href={cancelUrl} />}
                >
                    Cancelar
                </Button>

                <Button type="submit" disabled={form.processing}>
                    {form.processing && (
                        <LoaderCircle className="animate-spin" />
                    )}

                    {method === 'put' ? 'Actualizar ficha' : 'Crear ficha'}
                </Button>
            </div>
        </form>
    );
}
