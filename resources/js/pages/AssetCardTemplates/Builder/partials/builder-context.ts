import { createContext, useContext } from 'react';

import type {
    AssetCardTemplateSection,
    TemplateField,
    TemplateFieldType,
} from '@/types/assetCardTemplates/assetCardTemplate';

export interface BuilderContextValue {
    sections: AssetCardTemplateSection[];
    selectedSectionId: string | null;
    selectedFieldIndex: number | null;
    selectSection: (sectionId: string) => void;
    selectField: (sectionId: string, fieldIndex: number) => void;
    clearSelection: () => void;
    addSection: () => void;
    removeSection: (sectionId: string) => void;
    updateSection: (
        sectionId: string,
        patch: Partial<AssetCardTemplateSection>,
    ) => void;
    addField: (sectionId: string, type: TemplateFieldType) => void;
    removeField: (sectionId: string, fieldIndex: number) => void;
    updateField: (
        sectionId: string,
        fieldIndex: number,
        patch: Partial<TemplateField>,
    ) => void;
    reorderFields: (sectionId: string, fields: TemplateField[]) => void;
    reorderSections: (sections: AssetCardTemplateSection[]) => void;
}

export const BuilderContext = createContext<BuilderContextValue | null>(null);

export function useBuilder(): BuilderContextValue {
    const context = useContext(BuilderContext);

    if (context === null) {
        throw new Error('useBuilder must be used within a BuilderContext');
    }

    return context;
}
