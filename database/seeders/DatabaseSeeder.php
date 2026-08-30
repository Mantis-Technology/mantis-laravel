<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    /**
     * Default credentials of the automatic tenant administrator.
     *
     * @var string
     */
    public const DEFAULT_ADMIN_USERNAME = 'admin';

    /**
     * Default credentials of the automatic tenant administrator.
     *
     * @var string
     */
    public const DEFAULT_ADMIN_PASSWORD = 'admin';

    /**
     * Role name => permissions granted to it.
     *
     * @var array<string, list<string>>
     */
    private const ROLES = [
        // Tenant Administrator
        'tenant_admin' => [
            // Usuarios
            'query:view_users',
            'action:create_users',
            'action:update_users',
            'action:deactivate_users',

            // Roles
            'query:view_roles',
            'action:create_roles',
            'action:update_roles',
            'action:assign_roles',

            // Permisos
            'query:view_permissions',
            'action:assign_permissions',
            'action:revoke_permissions',

            // Configuración del tenant
            'query:view_tenant_configuration',
            'action:update_tenant_configuration',

            // Estructura organizacional
            'query:view_locations',
            'action:create_locations',
            'action:update_locations',
            'action:deactivate_locations',
        ],

        // Maintenance Chief
        'maintenance_chief' => [
            // Casos de mantenimiento
            'query:view_maintenance_cases',
            'action:assign_maintenance_cases',

            // Mantenimientos
            'query:view_maintenance',
            'action:schedule_preventive_maintenance',
            'query:schedule_preventive_maintenance',
            'action:schedule_corrective_maintenance',
            'query:schedule_corrective_maintenance',

            // Técnicos
            'query:view_technicians',
            'action:assign_technician',

            // Niveles de servicio
            'query:view_service_levels',
            'action:configure_service_levels',

            // Reportes y analítica
            'query:view_maintenance_reports',
            'action:export_maintenance_reports',

            // Recomendaciones predictivas
            'query:view_predictive_recommendations',
            'action:approve_predictive_recommendations',
        ],

        // Technician
        'technician' => [
            // Casos asignados
            'query:view_assigned_maintenance_cases',
            'action:update_assigned_maintenance_case',

            // Ejecución
            'action:start_maintenance',
            'action:update_maintenance_status',
            'action:complete_maintenance',

            // Registro de trabajo
            'action:log_time_and_resources',
            'action:add_maintenance_observations',
            'action:register_diagnosis',
            'action:register_solution',

            // Incidencias técnicas
            'action:report_technical_issues',
            'action:report_equipment_safety_issues',

            // Historial
            'query:view_asset_maintenance_history',
        ],

        // Operator
        'operator' => [
            // Consulta básica de activos
            'query:view_assigned_assets',

            // Reportes
            'action:create_failure_report',
            'action:create_safety_report',

            // Identificación mediante QR
            'action:report_asset_issue_via_qr',

            // Consulta de reportes propios
            'query:view_own_reports',
        ],
    ];

    /**
     * Seed the application's database.
     *
     * Roles, permissions and the default administrator are tenant data: they
     * are seeded automatically when a tenant is created, through the
     * `SeedDatabase` job of the tenant-creation pipeline. This seeder
     * therefore only does work in an initialized tenant context.
     */
    public function run(): void
    {
        if (! tenancy()->initialized) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::ROLES as $roleName => $permissions) {
            $role = SpatieRole::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            foreach ($permissions as $permission) {
                Permission::query()->firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'web',
                ]);
            }

            $role->syncPermissions($permissions);
        }

        $this->createDefaultAdministrator();
    }

    /**
     * Ensures the default tenant administrator (username: `admin`,
     * password: `admin`) exists. The password is only set on first creation,
     * so an already-created administrator keeps the credentials chosen later.
     */
    private function createDefaultAdministrator(): void
    {
        $tenant = tenant();

        if (! $tenant) {
            return;
        }

        $admin = User::withTrashed()->firstOrNew(['username' => self::DEFAULT_ADMIN_USERNAME]);

        $admin->fill([
            'name' => 'Administrador',
            'email' => $admin->email ?? "admin@{$tenant->id}.localhost",
        ]);

        if (! $admin->exists) {
            $admin->password = Hash::make(self::DEFAULT_ADMIN_PASSWORD);
        }

        $admin->save();

        $admin->syncRoles([Role::TENANT_ADMINISTRATOR->value]);
    }
}
