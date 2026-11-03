export interface TicketClosureResultOption {
    value: string;
    label: string;
    requires_observations: boolean;
}

export interface TicketClosure {
    id: number;
    work_performed: string;
    result: string;
    result_label: string;
    observations: string | null;
    closed_by: { id: number; name: string } | null;
    closed_at: string;
}
