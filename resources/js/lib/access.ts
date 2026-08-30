import type { RoleName, User } from '@/types/auth';

const ROLE_LABELS: Record<RoleName, string> = {
    tenant_admin: 'Administrador del tenant',
    maintenance_chief: 'Líder de mantenimientos',
    technician: 'Técnico',
    operator: 'Operador',
};

export function roleLabel(role: string): string {
    const name = role as RoleName;

    return ROLE_LABELS[name] ?? role.replaceAll('_', ' ');
}

export function hasRole(user: User | null, ...roles: RoleName[]): boolean {
    return !!user && roles.some((role) => user.roles.includes(role));
}

export function isTenantAdmin(user: User | null): boolean {
    return hasRole(user, 'tenant_admin');
}
