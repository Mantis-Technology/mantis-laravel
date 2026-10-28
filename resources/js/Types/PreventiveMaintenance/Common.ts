export type PreventiveStatusColor =
    | 'gray'
    | 'info'
    | 'warning'
    | 'success'
    | 'danger';

export interface PreventiveStatusOption {
    value: string;
    label: string;
    color: PreventiveStatusColor;
}

export interface PreventiveUser {
    id: number;
    name: string;
}

export interface PreventiveAssetCardRef {
    id: number;
    code: string;
}

export interface PreventiveCategoryRef {
    id: number;
    name: string;
}
