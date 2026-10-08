export interface TicketAssetCard {
    id: number;
    code: string;
}

export interface TicketUser {
    id: number;
    name: string;
}

export interface TicketLocation {
    id: number;
    name: string;
}

export interface TicketStatusOption {
    value: string;
    label: string;
    color: string;
}

export interface TicketTransition extends TicketStatusOption {
    action_label: string;
    requires_assignee: boolean;
}

export interface TicketMaintenanceCategoryOption {
    id: number;
    name: string;
    children?: TicketMaintenanceCategoryOption[];
}

export interface TicketSlaTarget {
    target_hours: number;
    due_at: string;
    happened_at: string | null;
    elapsed_hours: number;
    remaining_hours: number;
    state: string;
    state_label: string;
    state_color: string;
}

export interface TicketSla {
    has_service_level: boolean;
    response: TicketSlaTarget | null;
    resolution: TicketSlaTarget | null;
}

export interface TicketListItem {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: string;
    maintenance_type: TicketStatusOption | null;
    priority: TicketStatusOption | null;
    asset_card: TicketAssetCard | null;
    reporter: TicketUser | null;
    assignee: TicketUser | null;
    created_at: string | null;
}

export interface TicketStatusHistoryEntry {
    id: number;
    from_status: string | null;
    from_label: string | null;
    to_status: string;
    to_label: string;
    to_color: string;
    changed_by: TicketUser | null;
    note: string | null;
    created_at: string | null;
}

export interface TicketDetail extends TicketListItem {
    description: string | null;
    location: TicketLocation | null;
    category: TicketMaintenanceCategoryOption | null;
    categorized_by: TicketUser | null;
    categorized_at: string | null;
    updated_at: string | null;
    is_final: boolean;
    sla: TicketSla;
    status_transitions: TicketStatusHistoryEntry[];
    allowed_transitions: TicketTransition[];
}
