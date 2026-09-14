import type { Node, NodeProps } from '@xyflow/react';
import { X } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { fieldTypeLabel } from '@/types/assetCardTemplates/assetCardTemplate';

import type { TemplateField } from '@/types/assetCardTemplates/assetCardTemplate';

import { useBuilder } from './builder-context';

export interface FieldNodeData extends Record<string, unknown> {
    sectionId: string;
    fieldIndex: number;
    field: TemplateField;
}

export type FieldNodeType = Node<FieldNodeData, 'field'>;

export function FieldNode({ data }: NodeProps<FieldNodeType>) {
    const { selectedSectionId, selectedFieldIndex, removeField } = useBuilder();
    const { sectionId, fieldIndex, field } = data;
    const isSelected =
        selectedSectionId === sectionId && selectedFieldIndex === fieldIndex;

    return (
        <div
            className={cn(
                'flex h-full cursor-grab items-center gap-2 rounded-lg border bg-background px-2.5 text-sm shadow-sm transition-colors active:cursor-grabbing',
                isSelected
                    ? 'border-primary bg-primary/5 ring-1 ring-primary/30'
                    : 'hover:bg-muted',
            )}
        >
            <span className="w-4 shrink-0 text-center text-xs text-muted-foreground">
                {fieldIndex + 1}
            </span>

            <span className="min-w-0 flex-1 truncate">{field.label}</span>

            <Badge variant="outline" className="shrink-0">
                {fieldTypeLabel(field.type)}
            </Badge>

            {field.required && (
                <span className="shrink-0 text-destructive">*</span>
            )}

            <button
                type="button"
                className="nodrag shrink-0 text-muted-foreground transition-colors hover:text-destructive"
                onClick={(event) => {
                    event.stopPropagation();
                    removeField(sectionId, fieldIndex);
                }}
            >
                <X className="size-3.5" />
            </button>
        </div>
    );
}
