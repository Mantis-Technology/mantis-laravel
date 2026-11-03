import type {
    PreventiveAssetCardRef,
    PreventiveCategoryRef,
    PreventiveStatusColor,
    PreventiveStatusOption,
    PreventiveUser,
} from './Common';

export interface PlanFrequency {
    value: string;
    interval: number;
    label: string;
}

export interface PlanSummary {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: PreventiveStatusColor;
    frequency: PlanFrequency;
    asset_card: PreventiveAssetCardRef | null;
    category: PreventiveCategoryRef | null;
    responsible: PreventiveUser | null;
    starts_at: string;
    ends_at: string | null;
}

export interface PlanExecutionItem {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: PreventiveStatusColor;
    scheduled_at: string;
    is_overdue: boolean;
    assignee: PreventiveUser | null;
}

export interface PlanDetail extends PlanSummary {
    description: string | null;
    instructions: string | null;
    created_by: PreventiveUser | null;
    allowed_transitions: PreventiveStatusOption[];
    executions: PlanExecutionItem[];
}

export interface FrequencyOption {
    value: string;
    label: string;
}
