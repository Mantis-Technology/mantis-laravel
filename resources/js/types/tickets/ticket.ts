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

export interface TicketListItem {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: string;
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
    updated_at: string | null;
    is_final: boolean;
    status_transitions: TicketStatusHistoryEntry[];
    allowed_transitions: TicketTransition[];
}
