import { Background, ReactFlow } from '@xyflow/react';
import type { NodeChange, NodeTypes, XYPosition } from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useEffect, useRef, useState } from 'react';

import {
    CANVAS_PADDING,
    FIELD_GAP,
    FIELD_HEIGHT,
    fieldNodeId,
    layoutFields,
    layoutSections,
    orderFieldIndexesByPosition,
    orderSectionsByPosition,
    reorderFields,
} from '@/lib/builder-layout';

import { useBuilder } from './builder-context';
import { FieldNode } from './field-node';
import type { FieldNodeType } from './field-node';
import { SectionNode } from './section-node';
import type { SectionNodeType } from './section-node';

const nodeTypes: NodeTypes = { section: SectionNode, field: FieldNode };

type BuilderNodeType = SectionNodeType | FieldNodeType;

export function BuilderCanvas() {
    const {
        sections,
        selectedSectionId,
        selectedFieldIndex,
        selectSection,
        selectField,
        clearSelection,
        reorderSections,
        reorderFields: reorderSectionFields,
    } = useBuilder();
    const containerRef = useRef<HTMLDivElement>(null);
    const [canvasSize, setCanvasSize] = useState({ width: 0, height: 0 });
    const [sectionOverrides, setSectionOverrides] = useState<
        Record<string, XYPosition>
    >({});
    const [fieldOverrides, setFieldOverrides] = useState<
        Record<string, XYPosition>
    >({});

    useEffect(() => {
        const element = containerRef.current;

        if (element === null) {
            return;
        }

        const observer = new ResizeObserver((entries) => {
            const entry = entries[0];

            if (entry) {
                setCanvasSize({
                    width: entry.contentRect.width,
                    height: entry.contentRect.height,
                });
            }
        });

        observer.observe(element);

        return () => observer.disconnect();
    }, []);

    const sectionLayouts = layoutSections(
        sections,
        canvasSize.width,
        canvasSize.height,
    );
    const fieldLayouts = layoutFields(sections, sectionLayouts);
    const sectionIds = new Set(sections.map((section) => section.id));
    const contentHeight =
        sectionLayouts.reduce(
            (max, layout) => Math.max(max, layout.y + layout.height),
            CANVAS_PADDING,
        ) + CANVAS_PADDING;
    const flowHeight = Math.max(canvasSize.height, contentHeight);

    const sectionNodes: SectionNodeType[] = sections.map((section) => {
        const layout = sectionLayouts.find((item) => item.id === section.id);
        const override = sectionOverrides[section.id];

        return {
            id: section.id,
            type: 'section',
            position: override ?? { x: layout?.x ?? 0, y: layout?.y ?? 0 },
            style: {
                width: layout?.width ?? 320,
                height: layout?.height ?? 320,
            },
            data: { section },
        };
    });

    const fieldNodes: FieldNodeType[] = sections.flatMap((section) =>
        section.fields.map((field, fieldIndex) => {
            const layout = fieldLayouts.find(
                (item) =>
                    item.sectionId === section.id &&
                    item.fieldIndex === fieldIndex,
            );
            const id = fieldNodeId(section.id, fieldIndex);
            const override = fieldOverrides[id];

            return {
                id,
                type: 'field',
                parentId: section.id,
                extent: 'parent',
                position: override ?? { x: layout?.x ?? 0, y: layout?.y ?? 0 },
                style: {
                    width: layout?.width ?? 240,
                    height: layout?.height ?? FIELD_HEIGHT,
                },
                data: { sectionId: section.id, fieldIndex, field },
            };
        }),
    );

    const nodes: BuilderNodeType[] = [...sectionNodes, ...fieldNodes];

    function handleNodesChange(changes: NodeChange<BuilderNodeType>[]) {
        const sectionChanges: NodeChange<BuilderNodeType>[] = [];
        const fieldChanges: NodeChange<BuilderNodeType>[] = [];

        for (const change of changes) {
            if (
                change.type === 'position' &&
                change.dragging === true &&
                change.position
            ) {
                if (sectionIds.has(change.id)) {
                    sectionChanges.push(change);
                } else {
                    fieldChanges.push(change);
                }
            }
        }

        if (sectionChanges.length > 0) {
            setSectionOverrides((current) =>
                applyPositionChanges(current, sectionChanges),
            );
        }

        if (fieldChanges.length > 0) {
            setFieldOverrides((current) =>
                applyPositionChanges(current, fieldChanges),
            );
        }
    }

    function handleSectionDragStop(node: SectionNodeType) {
        const positions: Record<string, XYPosition> = {};

        for (const layout of sectionLayouts) {
            positions[layout.id] = { x: layout.x, y: layout.y };
        }

        Object.assign(positions, sectionOverrides, {
            [node.id]: node.position,
        });

        setSectionOverrides({});
        reorderSections(orderSectionsByPosition(sections, positions));
    }

    function handleFieldDragStop(node: FieldNodeType) {
        const { sectionId, fieldIndex } = node.data;
        const section = sections.find((item) => item.id === sectionId);

        if (!section) {
            return;
        }

        const positions: Record<number, XYPosition> = {};

        section.fields.forEach((_, index) => {
            const layout = fieldLayouts.find(
                (item) =>
                    item.sectionId === sectionId && item.fieldIndex === index,
            );

            positions[index] = fieldOverrides[
                fieldNodeId(sectionId, index)
            ] ?? {
                x: layout?.x ?? 0,
                y: layout?.y ?? index * (FIELD_HEIGHT + FIELD_GAP),
            };
        });

        positions[fieldIndex] = node.position;

        const orderedIndexes = orderFieldIndexesByPosition(
            section.fields.length,
            positions,
        );
        const selectedIndex =
            selectedSectionId === sectionId && selectedFieldIndex !== null
                ? orderedIndexes.indexOf(selectedFieldIndex)
                : -1;

        setFieldOverrides({});
        reorderSectionFields(
            sectionId,
            reorderFields(section.fields, orderedIndexes),
        );

        if (selectedIndex >= 0) {
            selectField(sectionId, selectedIndex);
        }
    }

    function handleNodeDragStop(
        _event: MouseEvent | TouchEvent,
        node: BuilderNodeType,
    ) {
        if (node.type === 'field') {
            handleFieldDragStop(node);

            return;
        }

        handleSectionDragStop(node);
    }

    return (
        <div
            ref={containerRef}
            className="relative min-w-0 flex-1 overflow-auto rounded-xl border bg-muted/20"
        >
            <ReactFlow
                nodes={nodes}
                edges={[]}
                nodeTypes={nodeTypes}
                style={{ height: flowHeight }}
                onNodesChange={handleNodesChange}
                onNodeDragStop={handleNodeDragStop}
                onNodeClick={(_event, node) => {
                    if (node.type === 'field') {
                        selectField(node.data.sectionId, node.data.fieldIndex);

                        return;
                    }

                    selectSection(node.id);
                }}
                onPaneClick={clearSelection}
                nodesConnectable={false}
                panOnDrag={false}
                panOnScroll={false}
                autoPanOnNodeDrag={false}
                autoPanOnSelection={false}
                preventScrolling={false}
                zoomOnScroll={false}
                zoomOnPinch={false}
                zoomOnDoubleClick={false}
                minZoom={1}
                maxZoom={1}
                fitView={false}
            >
                <Background />
            </ReactFlow>

            {sections.length === 0 && (
                <div className="pointer-events-none absolute inset-0 z-10 flex items-center justify-center p-6">
                    <p className="rounded-xl border border-dashed bg-card/90 px-4 py-3 text-center text-sm text-muted-foreground shadow-sm">
                        Añade una sección para empezar a construir el
                        formulario.
                    </p>
                </div>
            )}
        </div>
    );
}

function applyPositionChanges(
    current: Record<string, XYPosition>,
    changes: NodeChange<BuilderNodeType>[],
): Record<string, XYPosition> {
    let next = current;

    for (const change of changes) {
        if (change.type === 'position' && change.position) {
            if (next === current) {
                next = { ...current };
            }

            next[change.id] = change.position;
        }
    }

    return next;
}
