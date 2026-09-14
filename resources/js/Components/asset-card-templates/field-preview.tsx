import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

import type { TemplateField } from '@/types/assetCardTemplates/assetCardTemplate';

interface FieldPreviewProps {
    field: TemplateField;
}

export function FieldPreview({ field }: FieldPreviewProps) {
    switch (field.type) {
        case 'textarea':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Textarea
                        id={field.name}
                        name={field.name}
                        placeholder={field.placeholder}
                        disabled
                    />
                </Field>
            );

        case 'select':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Select items={field.options ?? []} disabled>
                        <SelectTrigger id={field.name} className="w-full">
                            <SelectValue
                                placeholder={
                                    field.placeholder ?? 'Seleccionar...'
                                }
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                {(field.options ?? []).map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </Field>
            );

        case 'checkbox':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <div className="flex items-center gap-2">
                        <Checkbox id={field.name} name={field.name} disabled />
                        <span className="text-sm text-muted-foreground">
                            {field.placeholder ?? 'Casilla de verificación'}
                        </span>
                    </div>
                </Field>
            );

        case 'radio':
            return (
                <Field>
                    <FieldLabel>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <div className="flex flex-col gap-2">
                        {(field.options ?? []).map((option) => (
                            <label
                                key={option.value}
                                className="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="radio"
                                    name={field.name}
                                    value={option.value}
                                    disabled
                                    className="size-4 accent-primary"
                                />
                                <span>{option.label}</span>
                            </label>
                        ))}
                    </div>
                </Field>
            );

        case 'file':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="file"
                        accept={field.mimeTypes?.join(',')}
                        disabled
                    />
                </Field>
            );

        case 'number':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="number"
                        placeholder={field.placeholder}
                        min={field.min}
                        max={field.max}
                        disabled
                    />
                </Field>
            );

        case 'date':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="date"
                        disabled
                    />
                </Field>
            );

        case 'email':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="email"
                        placeholder={field.placeholder}
                        disabled
                    />
                </Field>
            );

        case 'url':
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="url"
                        placeholder={field.placeholder}
                        disabled
                    />
                </Field>
            );

        case 'text':
        default:
            return (
                <Field>
                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>
                    <Input
                        id={field.name}
                        name={field.name}
                        type="text"
                        placeholder={field.placeholder}
                        disabled
                    />
                </Field>
            );
    }
}
