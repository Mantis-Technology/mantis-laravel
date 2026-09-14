import { FileText, Trash2, Undo2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';

import type { TemplateField } from '@/types/assetCardTemplates/assetCardTemplate';

interface AssetFileInputProps {
    field: TemplateField;
    error?: string;
    existingUrl?: string;
    removed: boolean;
    disabled?: boolean;
    onFileChange: (file: File | null) => void;
    onToggleRemove: () => void;
}

function formatMaxSize(bytes: number | undefined): string | null {
    if (bytes === undefined || bytes <= 0) {
        return null;
    }

    const megabytes = bytes / 1024 / 1024;

    return `${Math.round(megabytes * 10) / 10} MB`;
}

export function AssetFileInput({
    field,
    error,
    existingUrl,
    removed,
    disabled = false,
    onFileChange,
    onToggleRemove,
}: AssetFileInputProps) {
    const maxSize = formatMaxSize(field.maxFileSize);
    const hasExistingFile = !!existingUrl && !removed;

    return (
        <Field data-invalid={!!error}>
            <FieldLabel htmlFor={field.name}>
                {field.label}
                {field.required && <span className="text-destructive">*</span>}
            </FieldLabel>

            {hasExistingFile && (
                <div className="flex items-center gap-2 rounded-lg border bg-muted/30 px-2.5 py-1.5 text-sm">
                    <FileText className="size-4 shrink-0 text-muted-foreground" />

                    <a
                        href={existingUrl}
                        target="_blank"
                        rel="noreferrer"
                        className="min-w-0 flex-1 truncate underline"
                    >
                        Archivo actual
                    </a>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        title="Quitar archivo"
                        disabled={disabled}
                        onClick={onToggleRemove}
                    >
                        <Trash2 />
                    </Button>
                </div>
            )}

            {removed && (
                <div className="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/5 px-2.5 py-1.5 text-sm text-destructive">
                    <span className="min-w-0 flex-1 truncate">
                        Archivo marcado para eliminar
                    </span>

                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        title="Deshacer"
                        disabled={disabled}
                        onClick={onToggleRemove}
                    >
                        <Undo2 />
                    </Button>
                </div>
            )}

            <Input
                id={field.name}
                type="file"
                accept={field.mimeTypes?.join(',')}
                disabled={disabled}
                onChange={(event) =>
                    onFileChange(event.target.files?.[0] ?? null)
                }
            />

            {maxSize && (
                <FieldDescription>Tamaño máximo: {maxSize}</FieldDescription>
            )}

            <FieldError>{error}</FieldError>
        </Field>
    );
}
