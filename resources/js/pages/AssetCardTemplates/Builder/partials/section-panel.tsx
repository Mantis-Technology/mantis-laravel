import { Trash2, Undo2 } from 'lucide-react';
import { useState } from 'react';

import { FieldPreview } from '@/components/asset-card-templates/field-preview';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { MultiSelect } from '@/components/ui/multi-select';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

import type {
    AssetCardTemplateSection,
    TemplateField,
} from '@/types/assetCardTemplates/assetCardTemplate';
import {
    fieldTypeMeta,
    mimeTypeGroupsFor,
} from '@/types/assetCardTemplates/assetCardTemplate';

import { useBuilder } from './builder-context';
import { FieldOptionsEditor } from './field-options-editor';

interface SectionPanelProps {
    section: AssetCardTemplateSection | null;
    fieldIndex: number | null;
}

interface SectionSettingsProps {
    section: AssetCardTemplateSection;
    onUpdate: (
        sectionId: string,
        patch: Partial<AssetCardTemplateSection>,
    ) => void;
    onRemove: (sectionId: string) => void;
}

interface FieldSettingsProps {
    section: AssetCardTemplateSection;
    field: TemplateField;
    fieldIndex: number;
    onBack: () => void;
    onUpdate: (
        sectionId: string,
        fieldIndex: number,
        patch: Partial<TemplateField>,
    ) => void;
    onRemove: (sectionId: string, fieldIndex: number) => void;
}

const COLUMN_OPTIONS = Array.from({ length: 12 }, (_, index) => index + 1);

type FileSizeUnit = 'B' | 'KB' | 'MB';

const FILE_SIZE_UNITS: { value: FileSizeUnit; label: string }[] = [
    { value: 'B', label: 'Bytes' },
    { value: 'KB', label: 'KB' },
    { value: 'MB', label: 'MB' },
];

function fileSizeFactor(unit: FileSizeUnit): number {
    if (unit === 'MB') {
        return 1024 * 1024;
    }

    if (unit === 'KB') {
        return 1024;
    }

    return 1;
}

function inferFileSizeUnit(bytes: number): FileSizeUnit {
    if (bytes <= 0 || bytes % (1024 * 1024) === 0) {
        return 'MB';
    }

    if (bytes % 1024 === 0) {
        return 'KB';
    }

    return 'B';
}

function formatFileSizeValue(bytes: number, unit: FileSizeUnit): string {
    const value = bytes / fileSizeFactor(unit);

    return String(Math.round(value * 1000) / 1000);
}

export function SectionPanel({ section, fieldIndex }: SectionPanelProps) {
    const {
        selectSection,
        updateSection,
        updateField,
        removeSection,
        removeField,
    } = useBuilder();

    if (section === null) {
        return (
            <aside className="hidden w-80 shrink-0 items-center justify-center rounded-xl border bg-card p-6 text-center text-sm text-muted-foreground xl:flex">
                Selecciona una sección o un campo para configurarlo.
            </aside>
        );
    }

    const field =
        fieldIndex !== null ? (section.fields[fieldIndex] ?? null) : null;

    return (
        <aside className="flex w-80 shrink-0 flex-col overflow-y-auto rounded-xl border bg-card">
            {field !== null && fieldIndex !== null ? (
                <FieldSettings
                    key={`${section.id}-${fieldIndex}`}
                    section={section}
                    field={field}
                    fieldIndex={fieldIndex}
                    onBack={() => selectSection(section.id)}
                    onUpdate={updateField}
                    onRemove={removeField}
                />
            ) : (
                <SectionSettings
                    section={section}
                    onUpdate={updateSection}
                    onRemove={removeSection}
                />
            )}
        </aside>
    );
}

function SectionSettings({
    section,
    onUpdate,
    onRemove,
}: SectionSettingsProps) {
    return (
        <div className="flex flex-col gap-4 p-4">
            <div className="flex items-center justify-between">
                <h2 className="text-sm font-semibold">Sección</h2>

                <Badge variant="secondary">Orden #{section.order}</Badge>
            </div>

            <Field>
                <FieldLabel htmlFor="section-name">Título</FieldLabel>

                <Input
                    id="section-name"
                    value={section.name}
                    onChange={(event) =>
                        onUpdate(section.id, { name: event.target.value })
                    }
                />
            </Field>

            <Field>
                <FieldLabel htmlFor="section-description">
                    Descripción
                </FieldLabel>

                <Textarea
                    id="section-description"
                    value={section.description ?? ''}
                    onChange={(event) =>
                        onUpdate(section.id, {
                            description: event.target.value,
                        })
                    }
                />
            </Field>

            <Field>
                <FieldLabel>Columnas</FieldLabel>

                <Select
                    items={COLUMN_OPTIONS.map((value) => ({
                        value: String(value),
                        label: `${value} de 12`,
                    }))}
                    value={String(section.columns)}
                    onValueChange={(value) =>
                        onUpdate(section.id, { columns: Number(value) })
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue />
                    </SelectTrigger>

                    <SelectContent>
                        <SelectGroup>
                            {COLUMN_OPTIONS.map((value) => (
                                <SelectItem key={value} value={String(value)}>
                                    {value} de 12
                                </SelectItem>
                            ))}
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </Field>

            <Separator />

            <Button
                type="button"
                variant="destructive"
                onClick={() => onRemove(section.id)}
            >
                <Trash2 /> Eliminar sección
            </Button>
        </div>
    );
}

function FieldSettings({
    section,
    field,
    fieldIndex,
    onBack,
    onUpdate,
    onRemove,
}: FieldSettingsProps) {
    const meta = fieldTypeMeta(field.type);
    const TypeIcon = meta.icon;
    const [sizeUnit, setSizeUnit] = useState<FileSizeUnit>(() =>
        inferFileSizeUnit(field.maxFileSize ?? 0),
    );
    const [sizeValue, setSizeValue] = useState(() =>
        formatFileSizeValue(field.maxFileSize ?? 0, sizeUnit),
    );

    function update(patch: Partial<TemplateField>) {
        onUpdate(section.id, fieldIndex, patch);
    }

    function handleSizeChange(value: string) {
        setSizeValue(value);

        const numeric = Number(value);

        update({
            maxFileSize:
                Number.isFinite(numeric) && numeric > 0
                    ? Math.round(numeric * fileSizeFactor(sizeUnit))
                    : 0,
        });
    }

    function handleSizeUnitChange(unit: FileSizeUnit) {
        setSizeUnit(unit);
        setSizeValue(formatFileSizeValue(field.maxFileSize ?? 0, unit));
    }

    return (
        <div className="flex flex-col gap-4 p-4">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <h2 className="text-sm font-semibold">Campo</h2>

                    <Badge variant="secondary">Orden #{fieldIndex + 1}</Badge>
                </div>

                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={onBack}
                >
                    <Undo2 /> Volver
                </Button>
            </div>

            <div className="flex items-center gap-2">
                <Badge variant="outline" className={cn('gap-1', meta.color)}>
                    <TypeIcon />
                    {meta.label}
                </Badge>

                <span className="truncate text-xs text-muted-foreground">
                    {field.name}
                </span>
            </div>

            <Field>
                <FieldLabel htmlFor="field-label">Etiqueta</FieldLabel>

                <Input
                    id="field-label"
                    value={field.label}
                    onChange={(event) => update({ label: event.target.value })}
                />
            </Field>

            <Field>
                <FieldLabel htmlFor="field-name">Nombre (clave)</FieldLabel>

                <Input
                    id="field-name"
                    value={field.name}
                    onChange={(event) => update({ name: event.target.value })}
                />
            </Field>

            <Field>
                <FieldLabel htmlFor="field-placeholder">Placeholder</FieldLabel>

                <Input
                    id="field-placeholder"
                    value={field.placeholder ?? ''}
                    onChange={(event) =>
                        update({ placeholder: event.target.value })
                    }
                />
            </Field>

            <div className="flex items-center justify-between">
                <Label htmlFor="field-required">Obligatorio</Label>

                <Switch
                    id="field-required"
                    checked={field.required}
                    onCheckedChange={(checked) => update({ required: checked })}
                />
            </div>

            {field.type === 'number' && (
                <div className="grid grid-cols-2 gap-3">
                    <Field>
                        <FieldLabel htmlFor="field-min">Mínimo</FieldLabel>

                        <Input
                            id="field-min"
                            type="number"
                            value={field.min ?? 0}
                            onChange={(event) =>
                                update({ min: Number(event.target.value) })
                            }
                        />
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="field-max">Máximo</FieldLabel>

                        <Input
                            id="field-max"
                            type="number"
                            value={field.max ?? 0}
                            onChange={(event) =>
                                update({ max: Number(event.target.value) })
                            }
                        />
                    </Field>
                </div>
            )}

            {(field.type === 'select' || field.type === 'radio') && (
                <Field>
                    <FieldLabel>Opciones</FieldLabel>

                    <FieldOptionsEditor
                        options={field.options ?? []}
                        onChange={(options) => update({ options })}
                    />
                </Field>
            )}

            {field.type === 'file' && (
                <>
                    <Field>
                        <FieldLabel>Tipos de archivo permitidos</FieldLabel>

                        <MultiSelect
                            groups={mimeTypeGroupsFor(field.mimeTypes ?? [])}
                            value={field.mimeTypes ?? []}
                            onChange={(mimeTypes) => update({ mimeTypes })}
                            placeholder="Selecciona los tipos de archivo"
                        />

                        <FieldDescription>
                            Si no seleccionas ninguno, se aceptará cualquier
                            archivo.
                        </FieldDescription>
                    </Field>

                    <Field>
                        <FieldLabel htmlFor="field-max-size">
                            Tamaño máximo
                        </FieldLabel>

                        <div className="flex gap-2">
                            <Input
                                id="field-max-size"
                                type="number"
                                min={0}
                                step="any"
                                className="flex-1"
                                value={sizeValue}
                                onChange={(event) =>
                                    handleSizeChange(event.target.value)
                                }
                            />

                            <Select
                                items={FILE_SIZE_UNITS}
                                value={sizeUnit}
                                onValueChange={(value) => {
                                    if (value !== null) {
                                        handleSizeUnitChange(
                                            value as FileSizeUnit,
                                        );
                                    }
                                }}
                            >
                                <SelectTrigger className="w-24">
                                    <SelectValue />
                                </SelectTrigger>

                                <SelectContent>
                                    <SelectGroup>
                                        {FILE_SIZE_UNITS.map((unit) => (
                                            <SelectItem
                                                key={unit.value}
                                                value={unit.value}
                                            >
                                                {unit.label}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                </SelectContent>
                            </Select>
                        </div>

                        <FieldDescription>
                            Se guarda internamente en bytes.
                        </FieldDescription>
                    </Field>
                </>
            )}

            <Separator />

            <div className="rounded-lg border bg-muted/30 p-3">
                <FieldPreview field={field} />
            </div>

            <Button
                type="button"
                variant="destructive"
                onClick={() => onRemove(section.id, fieldIndex)}
            >
                <Trash2 /> Eliminar campo
            </Button>
        </div>
    );
}
