import { Plus, X } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

import type { SelectOption } from '@/types/assetCardTemplates/assetCardTemplate';

interface FieldOptionsEditorProps {
    options: SelectOption[];
    onChange: (options: SelectOption[]) => void;
    disabled?: boolean;
}

export function FieldOptionsEditor({
    options,
    onChange,
    disabled = false,
}: FieldOptionsEditorProps) {
    function updateOption(index: number, patch: Partial<SelectOption>) {
        onChange(
            options.map((option, position) =>
                position === index ? { ...option, ...patch } : option,
            ),
        );
    }

    function addOption() {
        onChange([
            ...options,
            {
                value: `option${options.length + 1}`,
                label: `Opción ${options.length + 1}`,
            },
        ]);
    }

    function removeOption(index: number) {
        onChange(options.filter((_, position) => position !== index));
    }

    return (
        <div className="space-y-2">
            {options.map((option, index) => (
                <div key={index} className="flex items-center gap-2">
                    <Input
                        value={option.value}
                        placeholder="valor"
                        disabled={disabled}
                        onChange={(event) =>
                            updateOption(index, { value: event.target.value })
                        }
                    />
                    <Input
                        value={option.label}
                        placeholder="etiqueta"
                        disabled={disabled}
                        onChange={(event) =>
                            updateOption(index, { label: event.target.value })
                        }
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        disabled={disabled}
                        onClick={() => removeOption(index)}
                    >
                        <X />
                    </Button>
                </div>
            ))}

            <Button
                type="button"
                variant="outline"
                size="sm"
                disabled={disabled}
                onClick={addOption}
            >
                <Plus /> Añadir opción
            </Button>
        </div>
    );
}
