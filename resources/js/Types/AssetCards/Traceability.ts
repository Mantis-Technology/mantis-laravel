export interface TraceabilityUser {
    id: number;
    name: string;
}

export interface TraceabilityEvent {
    key: string;
    source: 'ticket' | 'preventive';
    action: string;
    title: string;
    detail: string | null;
    note: string | null;
    user: TraceabilityUser | null;
    occurred_at: string | null;
    url: string;
}

export interface TraceabilityFilters {
    source: string | null;
    user: number | null;
}

export interface TraceabilityPagination {
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    prev_url: string | null;
    next_url: string | null;
}
