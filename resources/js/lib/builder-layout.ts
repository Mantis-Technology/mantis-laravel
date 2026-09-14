import type {
    AssetCardTemplateSection,
    TemplateField,
} from '@/types/assetCardTemplates/assetCardTemplate';

export const GRID_COLUMNS = 12;

export const NODE_GAP = 16;

export const CANVAS_PADDING = 24;

export const SECTION_HEADER_HEIGHT = 45;

export const SECTION_FOOTER_HEIGHT = 49;

export const SECTION_BODY_PADDING = 10;

export const SECTION_EMPTY_BODY_HEIGHT = 64;

export const FIELD_HEIGHT = 42;

export const FIELD_GAP = 8;

export const SECTION_ROW_TOLERANCE = 80;

export interface NodePosition {
    x: number;
    y: number;
}

export interface SectionLayout {
    id: string;
    x: number;
    y: number;
    width: number;
    height: number;
}

export interface FieldLayout {
    id: string;
    sectionId: string;
    fieldIndex: number;
    x: number;
    y: number;
    width: number;
    height: number;
}

export function fieldNodeId(sectionId: string, fieldIndex: number): string {
    return `${sectionId}::${fieldIndex}`;
}

export function sectionHeight(section: AssetCardTemplateSection): number {
    const bodyHeight =
        section.fields.length > 0
            ? section.fields.length * FIELD_HEIGHT +
              (section.fields.length - 1) * FIELD_GAP +
              SECTION_BODY_PADDING * 2
            : SECTION_EMPTY_BODY_HEIGHT;

    return SECTION_HEADER_HEIGHT + bodyHeight + SECTION_FOOTER_HEIGHT;
}

export function layoutSections(
    sections: AssetCardTemplateSection[],
    canvasWidth: number,
    canvasHeight = 0,
): SectionLayout[] {
    if (canvasWidth <= CANVAS_PADDING * 2) {
        return [];
    }

    const usableWidth = canvasWidth - CANVAS_PADDING * 2;
    const unit = (usableWidth - NODE_GAP * (GRID_COLUMNS - 1)) / GRID_COLUMNS;

    const rows: {
        entries: {
            section: AssetCardTemplateSection;
            x: number;
            width: number;
        }[];
        height: number;
    }[] = [];
    let currentRow = { entries: [], height: 0 } as (typeof rows)[number];
    let usedColumns = 0;
    let x = CANVAS_PADDING;

    for (const section of sections) {
        const columns = Math.min(Math.max(section.columns, 1), GRID_COLUMNS);

        if (usedColumns + columns > GRID_COLUMNS) {
            rows.push(currentRow);
            currentRow = { entries: [], height: 0 };
            usedColumns = 0;
            x = CANVAS_PADDING;
        }

        const width = columns * unit + (columns - 1) * NODE_GAP;

        currentRow.entries.push({ section, x, width });
        currentRow.height = Math.max(currentRow.height, sectionHeight(section));

        x += width + NODE_GAP;
        usedColumns += columns;
    }

    if (currentRow.entries.length > 0) {
        rows.push(currentRow);
    }

    const gapsHeight = Math.max(0, rows.length - 1) * NODE_GAP;
    const naturalTotal =
        rows.reduce((sum, row) => sum + row.height, 0) +
        gapsHeight +
        CANVAS_PADDING * 2;
    const extraPerRow =
        rows.length > 0
            ? Math.max(0, canvasHeight - naturalTotal) / rows.length
            : 0;

    const layouts: SectionLayout[] = [];
    let y = CANVAS_PADDING;

    for (const row of rows) {
        const height = row.height + extraPerRow;

        for (const entry of row.entries) {
            layouts.push({
                id: entry.section.id,
                x: entry.x,
                y,
                width: entry.width,
                height,
            });
        }

        y += height + NODE_GAP;
    }

    return layouts;
}

export function layoutFields(
    sections: AssetCardTemplateSection[],
    sectionLayouts: SectionLayout[],
): FieldLayout[] {
    const layouts: FieldLayout[] = [];
    const sectionLayoutById = new Map(
        sectionLayouts.map((layout) => [layout.id, layout]),
    );

    for (const section of sections) {
        const sectionLayout = sectionLayoutById.get(section.id);

        if (!sectionLayout) {
            continue;
        }

        section.fields.forEach((_, fieldIndex) => {
            layouts.push({
                id: fieldNodeId(section.id, fieldIndex),
                sectionId: section.id,
                fieldIndex,
                x: SECTION_BODY_PADDING,
                y:
                    SECTION_HEADER_HEIGHT +
                    SECTION_BODY_PADDING +
                    fieldIndex * (FIELD_HEIGHT + FIELD_GAP),
                width: sectionLayout.width - SECTION_BODY_PADDING * 2,
                height: FIELD_HEIGHT,
            });
        });
    }

    return layouts;
}

export function orderSectionsByPosition(
    sections: AssetCardTemplateSection[],
    positions: Record<string, NodePosition>,
): AssetCardTemplateSection[] {
    const items = sections.map((section) => ({
        section,
        position: positions[section.id] ?? { x: 0, y: 0 },
    }));
    const rows: {
        section: AssetCardTemplateSection;
        position: NodePosition;
    }[][] = [];

    for (const item of [...items].sort(
        (first, second) => first.position.y - second.position.y,
    )) {
        const row = rows.find(
            (candidate) =>
                Math.abs(candidate[0].position.y - item.position.y) <=
                SECTION_ROW_TOLERANCE,
        );

        if (row) {
            row.push(item);
        } else {
            rows.push([item]);
        }
    }

    return rows
        .flatMap((row) =>
            [...row].sort(
                (first, second) => first.position.x - second.position.x,
            ),
        )
        .map((item, index) => ({ ...item.section, order: index + 1 }));
}

export function orderFieldIndexesByPosition(
    fieldCount: number,
    positions: Record<number, NodePosition>,
): number[] {
    return Array.from({ length: fieldCount }, (_, index) => index).sort(
        (first, second) =>
            (positions[first]?.y ?? first) - (positions[second]?.y ?? second),
    );
}

export function reorderFields(
    fields: TemplateField[],
    orderedIndexes: number[],
): TemplateField[] {
    return orderedIndexes.map((index, order) => ({
        ...fields[index],
        order: order + 1,
    }));
}
