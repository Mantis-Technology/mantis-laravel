import type { Node, NodeProps } from '@xyflow/react';
import { GripVertical, Plus, Trash2 } from 'lucide-react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { FIELD_TYPE_OPTIONS } from '@/types/assetCardTemplates/assetCardTemplate';

import type { AssetCardTemplateSection } from '@/types/assetCardTemplates/assetCardTemplate';

import { useBuilder } from './builder-context';

export interface SectionNodeData extends Record<string, unknown> {
    section: AssetCardTemplateSection;
}

export type SectionNodeType = Node<SectionNodeData, 'section'>;

export function SectionNode({ data }: NodeProps<SectionNodeType>) {
    const { selectedSectionId, addField, removeSection } = useBuilder();

    const { section } = data;
    const isSelected = selectedSectionId === section.id;

    return (
        <div
            className={cn(
                'flex h-full flex-col overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm transition-shadow hover:shadow-md',
                isSelected && 'border-primary ring-2 ring-primary/30',
            )}
        >
            <div className="flex cursor-grab items-center gap-1.5 border-b bg-muted/40 px-2.5 py-2 select-none active:cursor-grabbing">
                <GripVertical className="size-4 shrink-0 text-muted-foreground" />

                <span className="min-w-0 flex-1 truncate text-sm font-medium">
                    {section.name}
                </span>

                <Badge variant="secondary">#{section.order}</Badge>

                <Badge variant="outline">{section.columns}/12</Badge>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    className="nodrag hover:bg-destructive/10 hover:text-destructive"
                    onClick={(event) => {
                        event.stopPropagation();
                        removeSection(section.id);
                    }}
                >
                    <Trash2 />
                </Button>
            </div>

            <div className="flex flex-1 items-center justify-center p-2.5">
                {section.fields.length === 0 && (
                    <p className="text-center text-xs text-muted-foreground">
                        Sin campos. Añade uno abajo.
                    </p>
                )}
            </div>

            <div className="border-t p-2">
                <DropdownMenu>
                    <DropdownMenuTrigger
                        render={
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="nodrag w-full border-dashed hover:border-primary/50 hover:bg-primary/5 hover:text-primary"
                            />
                        }
                    >
                        <Plus /> Añadir campo
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        align="center"
                        className="max-w-xl min-w-sm"
                    >
                        <DropdownMenuGroup>
                            <DropdownMenuLabel>Tipo de campo</DropdownMenuLabel>

                            {FIELD_TYPE_OPTIONS.map((option) => {
                                const OptionIcon = option.icon;

                                return (
                                    <DropdownMenuItem
                                        key={option.value}
                                        className="cursor-pointer"
                                        onClick={() =>
                                            addField(section.id, option.value)
                                        }
                                    >
                                        <span
                                            className={cn(
                                                'flex size-5 shrink-0 items-center justify-center rounded-md border',
                                                option.color,
                                            )}
                                        >
                                            <OptionIcon className="size-3" />
                                        </span>

                                        {option.label}
                                    </DropdownMenuItem>
                                );
                            })}
                        </DropdownMenuGroup>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    );
}
