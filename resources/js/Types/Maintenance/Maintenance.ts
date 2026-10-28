export interface MaintenanceCategory {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    parent_id: number | null;

    children: MaintenanceCategory[];
}

export interface ServiceLevel {
    id: number;
    maintenance_category_id: number | null;
    maintenance_category: { id: number; name: string } | null;
    maintenance_type: string | null;
    maintenance_type_label: string | null;
    priority: string | null;
    priority_label: string | null;
    response_hours: number;
    resolution_hours: number;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
}
