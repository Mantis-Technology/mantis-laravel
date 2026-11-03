export interface Location {
    id: number;
    name: string;
    description: string | null;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    parent_id: number | null;

    children: Location[];
}