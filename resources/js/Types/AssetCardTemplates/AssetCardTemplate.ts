import {
    AlignLeft,
    Calendar,
    CheckSquare,
    ChevronDown,
    CircleDot,
    Hash,
    Link as LinkIcon,
    Mail,
    Paperclip,
    Type,
} from 'lucide-react';

import type { LucideIcon } from 'lucide-react';

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
    icon: LucideIcon;
    color: string;
};

export const FIELD_TYPE_OPTIONS: FieldTypeOption[] = [
    {
        value: 'text',
        label: 'Texto',
        icon: Type,
        color: 'border-sky-500/20 bg-sky-500/10 text-sky-600 dark:text-sky-400',
    },
    {
        value: 'textarea',
        label: 'Área de texto',
        icon: AlignLeft,
        color: 'border-indigo-500/20 bg-indigo-500/10 text-indigo-600 dark:text-indigo-400',
    },
    {
        value: 'select',
        label: 'Select',
        icon: ChevronDown,
        color: 'border-violet-500/20 bg-violet-500/10 text-violet-600 dark:text-violet-400',
    },
    {
        value: 'checkbox',
        label: 'Checkbox',
        icon: CheckSquare,
        color: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
    },
    {
        value: 'radio',
        label: 'Radio',
        icon: CircleDot,
        color: 'border-teal-500/20 bg-teal-500/10 text-teal-600 dark:text-teal-400',
    },
    {
        value: 'date',
        label: 'Fecha',
        icon: Calendar,
        color: 'border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-400',
    },
    {
        value: 'file',
        label: 'Archivo',
        icon: Paperclip,
        color: 'border-rose-500/20 bg-rose-500/10 text-rose-600 dark:text-rose-400',
    },
    {
        value: 'number',
        label: 'Número',
        icon: Hash,
        color: 'border-orange-500/20 bg-orange-500/10 text-orange-600 dark:text-orange-400',
    },
    {
        value: 'email',
        label: 'Email',
        icon: Mail,
        color: 'border-cyan-500/20 bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
    },
    {
        value: 'url',
        label: 'URL',
        icon: LinkIcon,
        color: 'border-blue-500/20 bg-blue-500/10 text-blue-600 dark:text-blue-400',
    },
];

export function fieldTypeMeta(type: TemplateFieldType): FieldTypeOption {
    return (
        FIELD_TYPE_OPTIONS.find((option) => option.value === type) ??
        FIELD_TYPE_OPTIONS[0]
    );
}

export function fieldTypeLabel(type: TemplateFieldType): string {
    return fieldTypeMeta(type).label;
}

export type MimeTypeOption = {
    value: string;
    label: string;
};

export type MimeTypeGroup = {
    label: string;
    options: MimeTypeOption[];
};

export const MIME_TYPE_GROUPS: MimeTypeGroup[] = [
    {
        label: 'Imágenes',
        options: [
            { value: 'image/jpeg', label: 'Imagen JPG' },
            { value: 'image/png', label: 'Imagen PNG' },
            { value: 'image/gif', label: 'Imagen GIF' },
            { value: 'image/webp', label: 'Imagen WEBP' },
            { value: 'image/svg+xml', label: 'Imagen SVG' },
        ],
    },
    {
        label: 'Documentos',
        options: [
            { value: 'application/pdf', label: 'Documento PDF' },
            { value: 'application/msword', label: 'Word (DOC)' },
            {
                value: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                label: 'Word (DOCX)',
            },
            { value: 'text/plain', label: 'Texto plano' },
            { value: 'text/csv', label: 'CSV' },
        ],
    },
    {
        label: 'Hojas de cálculo',
        options: [
            { value: 'application/vnd.ms-excel', label: 'Excel (XLS)' },
            {
                value: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                label: 'Excel (XLSX)',
            },
        ],
    },
    {
        label: 'Otros',
        options: [
            { value: 'application/zip', label: 'Archivo ZIP' },
            { value: 'video/mp4', label: 'Video MP4' },
            { value: 'audio/mpeg', label: 'Audio MP3' },
        ],
    },
];

export function mimeTypeGroupsFor(values: string[]): MimeTypeGroup[] {
    const known = new Set(
        MIME_TYPE_GROUPS.flatMap((group) =>
            group.options.map((option) => option.value),
        ),
    );
    const custom = values
        .filter((value) => value !== '' && !known.has(value))
        .map((value) => ({ value, label: value }));

    if (custom.length === 0) {
        return MIME_TYPE_GROUPS;
    }

    return [...MIME_TYPE_GROUPS, { label: 'Personalizados', options: custom }];
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
