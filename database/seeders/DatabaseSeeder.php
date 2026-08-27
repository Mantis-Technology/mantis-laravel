<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Tenant Administrator
        /**
         * Responsable de administrar la configuración y los recursos
         * generales de una empresa dentro de MANTIS.
         *
         * Este rol no participa directamente en la ejecución de
         * mantenimientos, sino que gestiona usuarios, roles, permisos
         * y configuración del tenant.
         *
         * Permisos:
         * - Consultar y administrar usuarios.
         * - Asignar y revocar roles.
         * - Gestionar permisos disponibles dentro del tenant.
         * - Consultar y modificar la configuración de la empresa.
         * - Gestionar sedes, áreas y ubicaciones.
         * - Consultar información administrativa del tenant.
         */

        $tenantAdminRole = Role::create([
            'name' => 'tenant_admin',
        ]);

        $tenantAdminRole->givePermissionTo([
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | Maintenance Chief
        |--------------------------------------------------------------------------
        |
        | Responsable de supervisar y coordinar las actividades de mantenimiento
        | dentro de la empresa. Gestiona la asignación de casos, planificación,
        | seguimiento y supervisión de mantenimientos, además del análisis
        | de resultados y cumplimiento de niveles de servicio.
        |
        | La administración de roles y permisos no pertenece a este rol,
        | ya que corresponde a las responsabilidades de administración
        | de usuarios y autorización del sistema.
        |
        */

        $maintenanceChiefRole = Role::create([
            'name' => 'maintenance_chief',
        ]);

        $maintenanceChiefRole->givePermissionTo([
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | Technician
        |--------------------------------------------------------------------------
        |
        | Responsable de atender y ejecutar los casos de mantenimiento que
        | le sean asignados. Puede actualizar el progreso de sus casos,
        | registrar trabajo realizado, documentar diagnósticos y soluciones,
        | y reportar incidencias encontradas durante la intervención.
        |
        */

        $technicianRole = Role::create([
            'name' => 'technician',
        ]);

        $technicianRole->givePermissionTo([
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
        ]);

        /*
        |--------------------------------------------------------------------------
        | Operator
        |--------------------------------------------------------------------------
        |
        | Responsable de operar los equipos y comunicar anomalías observadas
        | durante la operación. Su interacción principal con MANTIS consiste
        | en identificar el activo y generar reportes de fallas o problemas
        | de seguridad.
        |
        */

        $operatorRole = Role::create([
            'name' => 'operator',
        ]);

        $operatorRole->givePermissionTo([
            // Consulta básica de activos
            'query:view_assigned_assets',

            // Reportes
            'action:create_failure_report',
            'action:create_safety_report',

            // Identificación mediante QR
            'action:report_asset_issue_via_qr',

            // Consulta de reportes propios
            'query:view_own_reports',
        ]);
    }
}
