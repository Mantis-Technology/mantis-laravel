import type { PredictiveRisk } from '@/Types/Statistics/PredictiveRisk';
import type { TicketStatusOption } from '@/Types/Tickets/Ticket';

export interface StatisticsFilters {
    date_from: string | null;
    date_to: string | null;
    maintenance_category_id: number | null;
    asset_card_id: number | null;
    technician_id: number | null;
    status: string | null;
    preventive_status: string | null;
}

export interface StatisticsSummary {
    total_tickets: number;
    open_tickets: number;
    resolved_tickets: number;
    closed_tickets: number;
    cancelled_tickets: number;
    total_preventive: number;
    completed_preventive: number;
    overdue_preventive: number;
    average_response_hours: number | null;
    average_resolution_hours: number | null;
    ticket_worked_hours: number;
    preventive_worked_hours: number;
    total_worked_hours: number;
    sla_evaluated: number;
    sla_met: number;
    sla_missed: number;
    sla_compliance_rate: number | null;
    sla_overdue_tickets: number;
    sla_at_risk_tickets: number;
}

export interface StatisticsTrendPoint {
    period: string;
    label: string;
    reported: number;
    resolved: number;
    preventive_completed: number;
}

export interface StatisticsDistributionRow extends TicketStatusOption {
    count: number;
}

export interface StatisticsCategoryRow {
    id: number | null;
    name: string;
    tickets: number;
    preventive: number;
}

export interface StatisticsTechnician {
    id: number;
    name: string;
    assigned_tickets: number;
    resolved_tickets: number;
    completed_preventive: number;
    average_resolution_hours: number | null;
    worked_hours: number;
    sla_compliance_rate: number | null;
}

export interface StatisticsAttentionPreventive {
    id: number;
    title: string;
    scheduled_at: string;
    days_overdue: number;
    asset_card: { id: number; code: string } | null;
    assignee: string | null;
}

export interface StatisticsAttentionTicket {
    id: number;
    title: string;
    status: string;
    status_label: string;
    status_color: string;
    priority: TicketStatusOption | null;
    asset_card: { id: number; code: string } | null;
    assignee: string | null;
    sla_state: string;
    sla_state_label: string;
    sla_state_color: string;
    due_at: string;
}

export interface StatisticsAttention {
    overdue_preventive: StatisticsAttentionPreventive[];
    sla_tickets: StatisticsAttentionTicket[];
}

export interface StatisticsCategoryOption {
    id: number;
    name: string;
}

export interface StatisticsAssetCardOption {
    id: number;
    code: string;
}

export interface StatisticsTechnicianOption {
    id: number;
    name: string;
}

export interface StatisticsOptions {
    maintenance_categories: StatisticsCategoryOption[];
    asset_cards: StatisticsAssetCardOption[];
    technicians: StatisticsTechnicianOption[];
    ticket_statuses: TicketStatusOption[];
    preventive_statuses: TicketStatusOption[];
}

export interface StatisticsPageProps {
    [key: string]: unknown;
    summary: StatisticsSummary;
    trend: StatisticsTrendPoint[];
    ticket_status_distribution: StatisticsDistributionRow[];
    preventive_status_distribution: StatisticsDistributionRow[];
    priority_distribution: StatisticsDistributionRow[];
    category_distribution: StatisticsCategoryRow[];
    technicians: StatisticsTechnician[];
    attention: StatisticsAttention;
    filters: StatisticsFilters;
    options: StatisticsOptions;
    predictive_risk: PredictiveRisk;
    predictive_risk_url: string;
    generated_at: string;
}
