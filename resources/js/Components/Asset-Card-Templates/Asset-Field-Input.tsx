import { Checkbox } from '@/Components/Ui/Checkbox';
import { Field, FieldError, FieldLabel } from '@/Components/Ui/Field';
import { Input } from '@/Components/Ui/Input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/Ui/Select';
import { Textarea } from '@/Components/Ui/Textarea';

import type { AssetFieldValue } from '@/Types/AssetCards/AssetCard';
import type { TemplateField } from '@/Types/AssetCardTemplates/AssetCardTemplate';

interface AssetFieldInputProps {
    field: TemplateField;
    value: AssetFieldValue;
    error?: string;
    disabled?: boolean;
    onChange?: (value: AssetFieldValue) => void;
}

export function AssetFieldInput({
    field,
    value,
    error,
    disabled = false,
    onChange,
}: AssetFieldInputProps) {
    const stringValue =
        value === null || value === undefined ? '' : String(value);

    const label = (
        <FieldLabel htmlFor={field.name}>
            {field.label}
            {field.required && <span className="text-destructive">*</span>}
        </FieldLabel>
    );

    switch (field.type) {
        case 'textarea':
            return (
                <Field data-invalid={!!error}>
                    {label}

                    <Textarea
                        id={field.name}
                        value={stringValue}
                        placeholder={field.placeholder}
                        disabled={disabled}
                        readOnly={disabled}
                        onChange={(event) => onChange?.(event.target.value)}
                    />

                    <FieldError>{error}</FieldError>
                </Field>
            );

        case 'select':
            return (
                <Field data-invalid={!!error}>
                    {label}

                    <Select
                        items={(field.options ?? []).map((option) => ({
                            value: option.value,
                            label: option.label,
                        }))}
                        value={typeof value === 'string' ? value : ''}
                        disabled={disabled}
                        onValueChange={(selected) => onChange?.(selected)}
                    >
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

                    <FieldError>{error}</FieldError>
                </Field>
            );

        case 'checkbox':
            return (
                <Field orientation="horizontal" data-invalid={!!error}>
                    <Checkbox
                        id={field.name}
                        checked={value === true}
                        disabled={disabled}
                        onCheckedChange={(checked) =>
                            onChange?.(checked === true)
                        }
                    />

                    <FieldLabel htmlFor={field.name}>
                        {field.label}
                        {field.required && (
                            <span className="text-destructive">*</span>
                        )}
                    </FieldLabel>

                    <FieldError>{error}</FieldError>
                </Field>
            );

        case 'radio':
            return (
                <Field data-invalid={!!error}>
                    {label}

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
                                    checked={value === option.value}
                                    disabled={disabled}
                                    onChange={() => onChange?.(option.value)}
                                    className="size-4 accent-primary"
                                />

                                <span>{option.label}</span>
                            </label>
                        ))}
                    </div>

                    <FieldError>{error}</FieldError>
                </Field>
            );

        case 'text':
        case 'email':
        case 'url':
        case 'date':
        case 'number':
        default:
            return (
                <Field data-invalid={!!error}>
                    {label}

                    <Input
                        id={field.name}
                        type={field.type}
                        value={stringValue}
                        placeholder={field.placeholder}
                        disabled={disabled}
                        readOnly={disabled}
                        min={field.type === 'number' ? field.min : undefined}
                        max={field.type === 'number' ? field.max : undefined}
                        onChange={(event) => onChange?.(event.target.value)}
                    />

                    <FieldError>{error}</FieldError>
                </Field>
            );
    }
}
