export type RoleName =
    'tenant_admin' | 'maintenance_chief' | 'technician' | 'operator';

export type User = {
    id: number;
    name: string;
    username: string;
    email: string;
    avatar?: string;
    roles: RoleName[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
};

export type Auth = {
    user: User;
};
