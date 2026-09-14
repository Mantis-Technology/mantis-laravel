import { router } from '@inertiajs/react';
import { Eye, History, Plus, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

import type {
    AssetCardTemplate,
    AssetCardTemplateSection,
    BuilderVersionOption,
    TemplateField,
    TemplateFieldType,
} from '@/types/assetCardTemplates/assetCardTemplate';
import { createField } from '@/types/assetCardTemplates/assetCardTemplate';

import { BuilderCanvas } from './partials/builder-canvas';
import { BuilderContext } from './partials/builder-context';
import type { BuilderContextValue } from './partials/builder-context';
import { SectionPanel } from './partials/section-panel';

interface Props {
    template: AssetCardTemplate;
    sections: AssetCardTemplateSection[];
    versions: BuilderVersionOption[];
    updateUrl: string;
    previewUrl: string;
    storeVersionUrl: string;
}

export default function Builder({
    template,
    sections: initialSections,
    versions,
    updateUrl,
    previewUrl,
    storeVersionUrl,
}: Props) {
    const [sections, setSections] = useState(initialSections);
    const [selectedSectionId, setSelectedSectionId] = useState<string | null>(
        null,
    );
    const [selectedFieldIndex, setSelectedFieldIndex] = useState<number | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);
    const [creatingVersion, setCreatingVersion] = useState(false);

    const selectedSection =
        sections.find((section) => section.id === selectedSectionId) ?? null;

    function selectSection(sectionId: string) {
        setSelectedSectionId(sectionId);
        setSelectedFieldIndex(null);
    }

    function selectField(sectionId: string, fieldIndex: number) {
        setSelectedSectionId(sectionId);
        setSelectedFieldIndex(fieldIndex);
    }

    function clearSelection() {
        setSelectedSectionId(null);
        setSelectedFieldIndex(null);
    }

    function addSection() {
        const id = crypto.randomUUID();
        const section: AssetCardTemplateSection = {
            id,
            name: `Sección ${sections.length + 1}`,
            description: null,
            order: sections.length + 1,
            columns: 12,
            fields: [],
        };

        setSections((current) => [...current, section]);
        selectSection(id);
    }

    function removeSection(sectionId: string) {
        setSections((current) =>
            current.filter((section) => section.id !== sectionId),
        );

        if (selectedSectionId === sectionId) {
            clearSelection();
        }
    }

    function updateSection(
        sectionId: string,
        patch: Partial<AssetCardTemplateSection>,
    ) {
        setSections((current) =>
            current.map((section) =>
                section.id === sectionId ? { ...section, ...patch } : section,
            ),
        );
    }

    function addField(sectionId: string, type: TemplateFieldType) {
        const section = sections.find((item) => item.id === sectionId);

        setSections((current) =>
            current.map((item) => {
                if (item.id !== sectionId) {
                    return item;
                }

                const field = createField(type, item.fields);

                return { ...item, fields: [...item.fields, field] };
            }),
        );

        if (section) {
            selectField(sectionId, section.fields.length);
        }
    }

    function removeField(sectionId: string, fieldIndex: number) {
        setSections((current) =>
            current.map((section) => {
                if (section.id !== sectionId) {
                    return section;
                }

                return {
                    ...section,
                    fields: section.fields
                        .filter((_, index) => index !== fieldIndex)
                        .map((field, index) => ({
                            ...field,
                            order: index + 1,
                        })),
                };
            }),
        );

        if (
            selectedSectionId === sectionId &&
            selectedFieldIndex === fieldIndex
        ) {
            setSelectedFieldIndex(null);
        }
    }

    function updateField(
        sectionId: string,
        fieldIndex: number,
        patch: Partial<TemplateField>,
    ) {
        setSections((current) =>
            current.map((section) => {
                if (section.id !== sectionId) {
                    return section;
                }

                return {
                    ...section,
                    fields: section.fields.map((field, index) =>
                        index === fieldIndex ? { ...field, ...patch } : field,
                    ),
                };
            }),
        );
    }

    function reorderFields(sectionId: string, fields: TemplateField[]) {
        setSections((current) =>
            current.map((section) =>
                section.id === sectionId ? { ...section, fields } : section,
            ),
        );
    }

    function handleSave() {
        router.put(
            updateUrl,
            { sections },
            {
                preserveScroll: true,
                onStart: () => setProcessing(true),
                onFinish: () => setProcessing(false),
                onError: () =>
                    toast.error('Revisa las secciones: hay datos inválidos.'),
            },
        );
    }

    function handleCreateVersion() {
        setCreatingVersion(true);

        router.post(
            storeVersionUrl,
            {},
            {
                preserveScroll: true,
                onFinish: () => setCreatingVersion(false),
            },
        );
    }

    const context: BuilderContextValue = {
        sections,
        selectedSectionId,
        selectedFieldIndex,
        selectSection,
        selectField,
        clearSelection,
        addSection,
        removeSection,
        updateSection,
        addField,
        removeField,
        updateField,
        reorderFields,
        reorderSections: setSections,
    };

    return (
        <BuilderContext.Provider value={context}>
            <div className="flex h-[calc(100svh-6rem)] flex-col gap-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <div>
                            <h1 className="text-lg font-semibold">
                                {template.name}
                            </h1>

                            <p className="text-sm text-muted-foreground">
                                {template.description ??
                                    'Diseña las secciones y campos de la plantilla'}
                            </p>
                        </div>

                        <Select
                            items={versions.map((option) => ({
                                value: String(option.version),
                                label: `v${option.version}`,
                            }))}
                            value={String(template.version)}
                            onValueChange={(value) => {
                                const option = versions.find(
                                    (candidate) =>
                                        String(candidate.version) === value,
                                );

                                if (option) {
                                    router.visit(option.url);
                                }
                            }}
                        >
                            <SelectTrigger className="w-24">
                                <SelectValue />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectGroup>
                                    {versions.map((option) => (
                                        <SelectItem
                                            key={option.version}
                                            value={String(option.version)}
                                        >
                                            v{option.version}
                                        </SelectItem>
                                    ))}
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            render={
                                <a
                                    href={previewUrl}
                                    target="_blank"
                                    rel="noreferrer"
                                />
                            }
                        >
                            <Eye /> Vista previa
                        </Button>

                        <Button
                            type="button"
                            variant="outline"
                            onClick={addSection}
                        >
                            <Plus /> Añadir sección
                        </Button>

                        <Button
                            type="button"
                            variant="secondary"
                            onClick={handleCreateVersion}
                            disabled={creatingVersion}
                        >
                            <History /> Nueva versión
                        </Button>

                        <Button
                            type="button"
                            onClick={handleSave}
                            disabled={processing}
                        >
                            <Save /> Guardar
                        </Button>
                    </div>
                </div>

                <div className="flex min-h-0 flex-1 gap-4">
                    <BuilderCanvas />

                    <SectionPanel
                        section={selectedSection}
                        fieldIndex={selectedFieldIndex}
                    />
                </div>
            </div>
        </BuilderContext.Provider>
    );
}
