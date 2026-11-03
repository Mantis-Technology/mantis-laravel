import type {
    PreventiveAssetCardRef,
    PreventiveCategoryRef,
    PreventiveStatusColor,
    PreventiveUser,
} from './Common';

export interface ExecutionSummary {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: PreventiveStatusColor;
    scheduled_at: string;
    original_scheduled_at: string | null;
    is_postponed: boolean;
    is_overdue: boolean;
    asset_card: PreventiveAssetCardRef | null;
    assignee: PreventiveUser | null;
    plan: { id: number; title: string } | null;
}

export interface ExecutionStatusTransition {
    id: number;
    from_status: string | null;
    from_label: string | null;
    to_status: string;
    to_label: string;
    to_color: PreventiveStatusColor;
    changed_by: PreventiveUser | null;
    note: string | null;
    created_at: string | null;
}

export interface ExecutionTransition {
    value: string;
    label: string;
    action_label: string;
    color: PreventiveStatusColor;
    requires_reason: boolean;
    requires_notes: boolean;
}

export interface ExecutionDetail extends ExecutionSummary {
    description: string | null;
    instructions: string | null;
    execution_notes: string | null;
    findings: string | null;
    category: PreventiveCategoryRef | null;
    started_at: string | null;
    completed_at: string | null;
    completed_by: PreventiveUser | null;
    is_final: boolean;
    status_transitions: ExecutionStatusTransition[];
    allowed_transitions: ExecutionTransition[];
    can_postpone: boolean;
    can_reassign: boolean;
}
