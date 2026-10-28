export type AssetFieldValue = string | number | boolean | null;

export type AssetFormValues = Record<string, Record<string, AssetFieldValue>>;

export type AssetFileValues = Record<string, Record<string, File | null>>;

export type AssetFileRemovals = Record<string, Record<string, boolean>>;

export type AssetFileUrls = Record<string, Record<string, string>>;

export type AssetCardListItem = {
    id: number;
    code: string;
    template_name: string | null;
    version: number;
    updated_at: string | null;
    show_url: string;
    edit_url: string;
    destroy_url: string;
};

export type AssetTemplateOption = {
    id: number;
    name: string;
    version: number;
    versions: number[];
};

export type AssetTemplateSummary = {
    id: number;
    name: string;
    version: number;
};

export type AssetCardSummary = {
    id: number;
    code: string;
};
