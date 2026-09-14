export type TemplateFieldType =
    | 'text'
    | 'textarea'
    | 'select'
    | 'checkbox'
    | 'radio'
    | 'date'
    | 'file'
    | 'number'
    | 'email'
    | 'url';

export type SelectOption = {
    value: string;
    label: string;
};

export type TemplateField = {
    name: string;
    label: string;
    type: TemplateFieldType;
    required: boolean;
    placeholder?: string;
    order: number;
    options?: SelectOption[];
    min?: number;
    max?: number;
    mimeTypes?: string[];
    maxFileSize?: number;
};

export type AssetCardTemplateSection = {
    id: string;
    name: string;
    description: string | null;
    order: number;
    columns: number;
    fields: TemplateField[];
};

export type AssetCardTemplate = {
    id: number;
    name: string;
    description: string | null;
    version: number;
};

export type AssetCardTemplateListItem = {
    id: number;
    name: string;
    description: string | null;
    version: number;
    sections_count: number;
    builder_url: string;
    store_version_url: string;
};

export type BuilderVersionOption = {
    version: number;
    url: string;
    is_current: boolean;
};

export type FieldTypeOption = {
    value: TemplateFieldType;
    label: string;
};

export const FIELD_TYPE_OPTIONS: FieldTypeOption[] = [
    { value: 'text', label: 'Texto' },
    { value: 'textarea', label: 'Área de texto' },
    { value: 'select', label: 'Select' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'radio', label: 'Radio' },
    { value: 'date', label: 'Fecha' },
    { value: 'file', label: 'Archivo' },
    { value: 'number', label: 'Número' },
    { value: 'email', label: 'Email' },
    { value: 'url', label: 'URL' },
];

export function fieldTypeLabel(type: TemplateFieldType): string {
    return (
        FIELD_TYPE_OPTIONS.find((option) => option.value === type)?.label ??
        type
    );
}

export function createField(
    type: TemplateFieldType,
    fields: TemplateField[],
): TemplateField {
    const usedNames = new Set(fields.map((field) => field.name));
    let index = fields.length + 1;

    while (usedNames.has(`field_${index}`)) {
        index += 1;
    }

    const field: TemplateField = {
        name: `field_${index}`,
        label: `Campo ${index}`,
        type,
        required: false,
        order: fields.length + 1,
    };

    switch (type) {
        case 'number':
            return { ...field, min: 0, max: 100 };

        case 'select':
        case 'radio':
            return {
                ...field,
                options: [{ value: 'option1', label: 'Opción 1' }],
            };

        case 'file':
            return { ...field, mimeTypes: [], maxFileSize: 0 };

        default:
            return field;
    }
}
