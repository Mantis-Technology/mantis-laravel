<?php

declare(strict_types=1);

namespace App\Services\PredictiveDemo;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Enums\PreventiveExecutionStatus;
use App\Enums\PreventiveFrequencyType;
use App\Enums\Role;
use App\Enums\TicketClosureResult;
use App\Enums\TicketStatus;
use App\Models\AssetCard;
use App\Models\AssetCardTemplate;
use App\Models\AssetCardTemplateSection;
use App\Models\CardTemplateVersion;
use App\Models\Location;
use App\Models\MaintenanceCategory;
use App\Models\PreventiveMaintenanceExecution;
use App\Models\PreventiveMaintenancePlan;
use App\Models\ServiceLevel;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Faker\Generator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Genera el historial operativo sintético de una empresa de demostración.
 *
 * La simulación mantiene una señal aprendible: la probabilidad mensual de
 * falla depende del tipo de activo, su antigüedad, criticidad, sede, fallas
 * previas y cumplimiento preventivo. El modelo nunca ve esa probabilidad
 * latente; solo ve las variables que quedan registradas en MANTIS.
 */
class GenerateTenantHistory
{
    private Generator $faker;

    /**
     * @param  array<string, mixed>  $company
     */
    public function execute(Tenant $tenant, array $company): void
    {
        if (AssetCard::query()->where('code', 'like', 'DEMO-%')->exists()) {
            return;
        }

        $profile = $company['profile'];

        $this->faker = fake();
        $this->faker->seed((int) config('predictive.demo.seed') + crc32((string) $tenant->getKey()));

        $historyStart = Carbon::parse((string) config('predictive.demo.history_start'))->startOfDay();
        $historyEnd = Carbon::parse((string) config('predictive.demo.history_end'))->endOfDay();

        try {
            $this->ensureRoles();
            $users = $this->createUsers($tenant);
            $categories = $this->createCategories($profile);
            $locations = $this->createLocations($profile);

            $this->createServiceLevels();

            [, $section] = $this->createTemplate($profile, $locations);

            $assets = $this->createAssets(
                $tenant,
                $profile,
                $locations,
                $section,
                $historyStart,
            );

            $this->simulateHistory(
                $assets,
                $users,
                $categories,
                $profile,
                $historyStart,
                $historyEnd,
            );
        } finally {
            Carbon::setTestNow();
        }
    }

    private function ensureRoles(): void
    {
        foreach (Role::cases() as $role) {
            SpatieRole::findOrCreate($role->value, 'web');
        }
    }

    /**
     * @return array{chief: User, technicians: list<User>, operators: list<User>}
     */
    private function createUsers(Tenant $tenant): array
    {
        $admin = User::withTrashed()->firstOrNew(['username' => 'admin']);
        $admin->fill([
            'name' => 'Administrador',
            'email' => $admin->email ?? 'admin@'.$tenant->id.'.localhost',
        ]);

        if (! $admin->exists) {
            $admin->password = Hash::make('admin');
        }

        $admin->save();
        $admin->syncRoles([Role::TENANT_ADMINISTRATOR->value]);

        $chief = User::factory()->create([
            'name' => 'Jefe de Mantenimiento',
            'username' => 'jefe',
            'email' => 'jefe@'.$tenant->id.'.test',
        ]);
        $chief->assignRole(Role::MAINTENANCE_CHIEF->value);

        $technicians = [];
        $technicianNames = [
            'Técnico Eléctrico',
            'Técnico Mecánico',
            'Técnico Hidráulico',
            'Técnico Electrónico',
            'Técnico de Lubricación',
            'Técnico de Instrumentación',
        ];

        foreach ($technicianNames as $index => $name) {
            $technician = User::factory()->create([
                'name' => $name,
                'username' => 'tecnico'.($index + 1),
                'email' => 'tecnico'.($index + 1).'@'.$tenant->id.'.test',
            ]);
            $technician->assignRole(Role::TECHNICIAN->value);
            $technicians[] = $technician;
        }

        $operators = [];
        $operatorNames = [
            'Operador de Producción',
            'Operador de Turno',
            'Operador de Almacén',
            'Operador de Planta',
        ];

        foreach ($operatorNames as $index => $name) {
            $operator = User::factory()->create([
                'name' => $name,
                'username' => 'operador'.($index + 1),
                'email' => 'operador'.($index + 1).'@'.$tenant->id.'.test',
            ]);
            $operator->assignRole(Role::OPERATOR->value);
            $operators[] = $operator;
        }

        return [
            'chief' => $chief,
            'technicians' => $technicians,
            'operators' => $operators,
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{failure: list<MaintenanceCategory>, preventive: MaintenanceCategory}
     */
    private function createCategories(array $profile): array
    {
        $failure = [];

        foreach ($profile['failure_categories'] as $name) {
            $failure[] = MaintenanceCategory::query()->firstOrCreate(
                ['name' => $name],
                ['description' => 'Categoría de falla de demostración', 'is_active' => true],
            );
        }

        $preventive = MaintenanceCategory::query()->firstOrCreate(
            ['name' => 'Preventivo'],
            ['description' => 'Mantenimiento preventivo planificado', 'is_active' => true],
        );

        return ['failure' => $failure, 'preventive' => $preventive];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return list<array{site: Location, zone: Location}>
     */
    private function createLocations(array $profile): array
    {
        $locations = [];

        foreach ($profile['sites'] as $siteData) {
            $site = Location::query()->firstOrCreate(
                ['parent_id' => null, 'name' => $siteData['name']],
                ['description' => 'Sede de demostración', 'is_active' => true],
            );

            foreach ($siteData['zones'] as $zoneName) {
                $zone = Location::query()->firstOrCreate(
                    ['parent_id' => $site->id, 'name' => $zoneName],
                    ['description' => 'Zona de demostración', 'is_active' => true],
                );

                $locations[] = ['site' => $site, 'zone' => $zone];
            }
        }

        return $locations;
    }

    private function createServiceLevels(): void
    {
        $levels = [
            MaintenancePriority::Low->value => ['response' => 8, 'resolution' => 72],
            MaintenancePriority::Medium->value => ['response' => 4, 'resolution' => 48],
            MaintenancePriority::High->value => ['response' => 2, 'resolution' => 24],
            MaintenancePriority::Critical->value => ['response' => 1, 'resolution' => 8],
        ];

        foreach ($levels as $priority => $hours) {
            ServiceLevel::query()->firstOrCreate(
                [
                    'maintenance_category_id' => null,
                    'maintenance_type' => null,
                    'priority' => $priority,
                ],
                [
                    'response_hours' => $hours['response'],
                    'resolution_hours' => $hours['resolution'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array{site: Location, zone: Location}>  $locations
     * @return array{0: AssetCardTemplate, 1: AssetCardTemplateSection}
     */
    private function createTemplate(array $profile, array $locations): array
    {
        $template = AssetCardTemplate::query()->firstOrCreate(
            ['name' => 'Ficha de activo industrial'],
            ['description' => 'Plantilla de demostración para analítica predictiva', 'version' => 1],
        );

        CardTemplateVersion::query()->firstOrCreate([
            'asset_card_template_id' => $template->id,
            'version' => 1,
        ]);

        $siteNames = array_values(array_unique(array_map(
            fn (array $location): string => $location['site']->name,
            $locations,
        )));

        $section = AssetCardTemplateSection::query()->firstOrCreate(
            [
                'asset_card_template_id' => $template->id,
                'version' => 1,
            ],
            [
                'name' => 'Identificación y operación',
                'description' => 'Datos base del activo',
                'order' => 1,
                'columns' => 12,
                'fields' => [
                    ['name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => true, 'order' => 1],
                    [
                        'name' => 'tipo_activo',
                        'label' => 'Tipo de activo',
                        'type' => 'select',
                        'required' => true,
                        'order' => 2,
                        'options' => array_map(
                            fn (string $type): array => ['value' => $type, 'label' => $type],
                            $profile['asset_types'],
                        ),
                    ],
                    ['name' => 'marca', 'label' => 'Marca', 'type' => 'text', 'required' => false, 'order' => 3],
                    ['name' => 'modelo', 'label' => 'Modelo', 'type' => 'text', 'required' => false, 'order' => 4],
                    ['name' => 'fecha_instalacion', 'label' => 'Fecha de instalación', 'type' => 'date', 'required' => true, 'order' => 5],
                    [
                        'name' => 'criticidad',
                        'label' => 'Criticidad',
                        'type' => 'select',
                        'required' => true,
                        'order' => 6,
                        'options' => [
                            ['value' => 'low', 'label' => 'Baja'],
                            ['value' => 'medium', 'label' => 'Media'],
                            ['value' => 'high', 'label' => 'Alta'],
                            ['value' => 'critical', 'label' => 'Crítica'],
                        ],
                    ],
                    [
                        'name' => 'sede',
                        'label' => 'Sede',
                        'type' => 'select',
                        'required' => true,
                        'order' => 7,
                        'options' => array_map(
                            fn (string $site): array => ['value' => $site, 'label' => $site],
                            $siteNames,
                        ),
                    ],
                ],
            ],
        );

        return [$template, $section];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  list<array{site: Location, zone: Location}>  $locations
     * @return list<array<string, mixed>>
     */
    private function createAssets(
        Tenant $tenant,
        array $profile,
        array $locations,
        AssetCardTemplateSection $section,
        Carbon $historyStart,
    ): array {
        $assets = [];
        $count = (int) config('predictive.demo.assets_per_tenant');
        $sectionId = $section->getKey();

        $brands = ['Siemens', 'ABB', 'Schneider', 'Cummins', 'Atlas Copco', 'WEG'];
        $models = ['X1', 'X2', 'Pro', 'Industrial', 'Series 300', 'Max'];

        for ($index = 1; $index <= $count; $index++) {
            $type = $profile['asset_types'][($index - 1) % count($profile['asset_types'])];
            $location = $this->faker->randomElement($locations);
            $siteName = $location['site']->name;
            $zoneId = $location['zone']->id;
            $criticality = $this->weightedChoice([
                'low' => 20,
                'medium' => 45,
                'high' => 25,
                'critical' => 10,
            ]);

            $installedAt = Carbon::instance($this->faker->dateTimeBetween(
                $historyStart->copy()->subYears(10),
                $historyStart->copy()->subMonth(),
            ))->startOfDay();

            Carbon::setTestNow($installedAt);

            $asset = AssetCard::query()->create([
                'asset_card_template_id' => $section->asset_card_template_id,
                'version' => 1,
                'code' => sprintf('DEMO-%04d', $index),
                'data' => [
                    $sectionId => [
                        'nombre' => ['type' => 'text', 'value' => $type.' '.$index],
                        'tipo_activo' => ['type' => 'select', 'value' => $type],
                        'marca' => ['type' => 'text', 'value' => $this->faker->randomElement($brands)],
                        'modelo' => ['type' => 'text', 'value' => $this->faker->randomElement($models)],
                        'fecha_instalacion' => ['type' => 'date', 'value' => $installedAt->toDateString()],
                        'criticidad' => ['type' => 'select', 'value' => $criticality],
                        'sede' => ['type' => 'select', 'value' => $siteName],
                    ],
                ],
            ]);

            $assets[] = [
                'model' => $asset,
                'type' => $type,
                'site' => $siteName,
                'zone_id' => $zoneId,
                'criticality' => $criticality,
                'installed_at' => $installedAt,
                'type_factor' => 0.75 + (crc32($type) % 60) / 100,
                'site_factor' => 0.85 + (crc32($siteName) % 30) / 100,
            ];
        }

        return $assets;
    }

    /**
     * @param  list<array<string, mixed>>  $assets
     * @param  array{chief: User, technicians: list<User>, operators: list<User>}  $users
     * @param  array{failure: list<MaintenanceCategory>, preventive: MaintenanceCategory}  $categories
     * @param  array<string, mixed>  $profile
     */
    private function simulateHistory(
        array $assets,
        array $users,
        array $categories,
        array $profile,
        Carbon $historyStart,
        Carbon $historyEnd,
    ): void {
        foreach ($assets as $asset) {
            $technician = $this->faker->randomElement($users['technicians']);
            $plan = $this->createPreventivePlan($asset, $technician, $categories['preventive'], $users['chief'], $historyStart);

            $preventiveDates = $this->preventiveDates($plan, $historyStart, $historyEnd);
            $lastPreventiveAt = null;
            $failureDates = [];

            $month = $historyStart->copy()->startOfMonth();

            while ($month->lessThanOrEqualTo($historyEnd)) {
                $monthEnd = $month->copy()->endOfMonth();

                foreach ($preventiveDates as $preventiveDate) {
                    if ($preventiveDate->lessThan($month) || $preventiveDate->greaterThan($monthEnd)) {
                        continue;
                    }

                    $completedAt = $this->createPreventiveExecution(
                        $plan,
                        $preventiveDate,
                        $technician,
                        $profile['preventive_compliance'],
                        $historyEnd,
                    );

                    if ($completedAt !== null) {
                        $lastPreventiveAt = $completedAt;
                    }
                }

                $hazard = $this->monthlyHazard(
                    $asset,
                    $lastPreventiveAt,
                    count($failureDates),
                    (float) $profile['base_hazard'],
                    $month,
                );

                if ($this->faker->boolean((int) round(min(0.85, $hazard) * 100))) {
                    $failureAt = Carbon::instance($this->faker->dateTimeBetween($month, $monthEnd));

                    $this->createCorrectiveTicket(
                        $asset,
                        $failureAt,
                        $users,
                        $categories['failure'],
                    );

                    $failureDates[] = $failureAt;
                }

                $month = $month->addMonth();
            }
        }
    }

    /**
     * @param  array<string, mixed>  $asset
     */
    private function createPreventivePlan(
        array $asset,
        User $technician,
        MaintenanceCategory $category,
        User $chief,
        Carbon $historyStart,
    ): PreventiveMaintenancePlan {
        $startsAt = $historyStart->copy()->addDays($this->faker->numberBetween(0, 25));

        Carbon::setTestNow($startsAt);

        return PreventiveMaintenancePlan::query()->create([
            'asset_card_id' => $asset['model']->id,
            'maintenance_category_id' => $category->id,
            'title' => 'Mantenimiento preventivo '.$asset['type'],
            'description' => 'Plan preventivo sintético de demostración',
            'instructions' => 'Revisión general y registro de hallazgos.',
            'frequency_type' => PreventiveFrequencyType::Monthly,
            'interval' => $this->faker->randomElement([1, 2, 3]),
            'starts_at' => $startsAt,
            'ends_at' => null,
            'responsible_id' => $technician->id,
            'created_by_id' => $chief->id,
        ]);
    }

    /**
     * @return list<Carbon>
     */
    private function preventiveDates(
        PreventiveMaintenancePlan $plan,
        Carbon $historyStart,
        Carbon $historyEnd,
    ): array {
        $dates = [];
        $date = Carbon::instance($plan->starts_at)->startOfDay();
        $interval = max(1, $plan->interval);

        while ($date->lessThanOrEqualTo($historyEnd)) {
            if ($date->greaterThanOrEqualTo($historyStart)) {
                $dates[] = $date->copy();
            }

            $date = $date->addMonthsNoOverflow($interval);
        }

        return $dates;
    }

    private function createPreventiveExecution(
        PreventiveMaintenancePlan $plan,
        Carbon $scheduledAt,
        User $technician,
        float $compliance,
        Carbon $historyEnd,
    ): ?Carbon {
        Carbon::setTestNow($scheduledAt);

        $execution = PreventiveMaintenanceExecution::query()->create([
            'preventive_maintenance_plan_id' => $plan->id,
            'asset_card_id' => $plan->asset_card_id,
            'maintenance_category_id' => $plan->maintenance_category_id,
            'title' => $plan->title,
            'description' => $plan->description,
            'instructions' => $plan->instructions,
            'scheduled_at' => $scheduledAt,
            'assigned_to_id' => $technician->id,
        ]);

        if ($scheduledAt->greaterThan($historyEnd->copy()->subDays(15))) {
            return null;
        }

        if ($this->faker->boolean((int) round($compliance * 100))) {
            $execution->execution_notes = 'Revisión completada sin novedades relevantes.';
            $execution->findings = $this->faker->randomElement([
                'Sin hallazgos.',
                'Se ajustó la tensión de bandas.',
                'Se limpió el filtro de aire.',
                'Se verificó el nivel de lubricante.',
                'Se recomienda seguimiento en el próximo ciclo.',
            ]);

            Carbon::setTestNow($scheduledAt->copy()->addHours($this->faker->numberBetween(1, 4)));
            $execution->transitionTo(PreventiveExecutionStatus::InProgress, $technician->id);

            Carbon::setTestNow($scheduledAt->copy()->addHours($this->faker->numberBetween(5, 12)));
            $execution->transitionTo(PreventiveExecutionStatus::Completed, $technician->id, 'Ejecución completada');

            return $execution->completed_at !== null
                ? Carbon::instance($execution->completed_at)
                : null;
        }

        $status = $this->faker->boolean(60)
            ? PreventiveExecutionStatus::Skipped
            : PreventiveExecutionStatus::Cancelled;

        Carbon::setTestNow($scheduledAt->copy()->addHours($this->faker->numberBetween(1, 6)));
        $execution->transitionTo(
            $status,
            $technician->id,
            $status === PreventiveExecutionStatus::Skipped
                ? 'No se pudo ejecutar por disponibilidad del activo.'
                : 'Cancelado por cambio de programación de planta.',
        );

        return null;
    }

    /**
     * @param  array<string, mixed>  $asset
     */
    private function monthlyHazard(
        array $asset,
        ?Carbon $lastPreventiveAt,
        int $priorFailures,
        float $baseHazard,
        Carbon $month,
    ): float {
        $ageDays = $asset['installed_at']->diffInDays($month);
        $ageFactor = min(1.6, 0.8 + ($ageDays / 3650) * 0.6);
        $criticalityFactor = match ($asset['criticality']) {
            'critical' => 1.55,
            'high' => 1.25,
            'medium' => 1.0,
            default => 0.8,
        };

        $preventiveFactor = 1.1;

        if ($lastPreventiveAt !== null) {
            $daysSincePreventive = $lastPreventiveAt->diffInDays($month);

            $preventiveFactor = match (true) {
                $daysSincePreventive <= 30 => 0.65,
                $daysSincePreventive <= 90 => 0.85,
                default => 1.0,
            };
        }

        $priorFailureFactor = 1 + min(0.8, $priorFailures * 0.08);

        return max(
            0.01,
            $baseHazard
                * $asset['type_factor']
                * $asset['site_factor']
                * $ageFactor
                * $criticalityFactor
                * $preventiveFactor
                * $priorFailureFactor,
        );
    }

    /**
     * @param  array<string, mixed>  $asset
     * @param  array{chief: User, technicians: list<User>, operators: list<User>}  $users
     * @param  list<MaintenanceCategory>  $categories
     */
    private function createCorrectiveTicket(
        array $asset,
        Carbon $failureAt,
        array $users,
        array $categories,
    ): void {
        Carbon::setTestNow($failureAt);

        $category = $this->faker->randomElement($categories);
        $technician = $this->faker->randomElement($users['technicians']);
        $priority = $this->priorityFor($asset['criticality']);

        $ticket = Ticket::query()->create([
            'asset_card_id' => $asset['model']->id,
            'reported_by' => $this->faker->randomElement($users['operators'])->id,
            'location_id' => $asset['zone_id'],
            'title' => 'Falla de '.$asset['type'].': '.$category->name,
            'description' => 'Reporte sintético de falla para el activo '.$asset['model']->code.'.',
        ]);

        Carbon::setTestNow($failureAt->copy()->addMinutes($this->faker->numberBetween(15, 120)));
        $ticket->categorize(
            $category,
            MaintenanceType::Corrective,
            $priority,
            $users['chief']->id,
            'Clasificación de demostración',
        );

        Carbon::setTestNow($failureAt->copy()->addHours($this->faker->numberBetween(1, 4)));
        $ticket->assigned_to = $technician->id;
        $ticket->save();
        $ticket->transitionTo(TicketStatus::Assigned, $users['chief']->id, 'Asignación de demostración');

        if ($failureAt->greaterThan(Carbon::parse((string) config('predictive.demo.history_end'))->subDays(20))
            && $this->faker->boolean(40)) {
            return;
        }

        Carbon::setTestNow($failureAt->copy()->addHours($this->faker->numberBetween(2, 10)));
        $ticket->transitionTo(TicketStatus::InProgress, $technician->id);

        $resolutionHours = match ($priority) {
            MaintenancePriority::Critical => $this->faker->numberBetween(2, 12),
            MaintenancePriority::High => $this->faker->numberBetween(4, 24),
            MaintenancePriority::Medium => $this->faker->numberBetween(8, 48),
            default => $this->faker->numberBetween(12, 72),
        };

        Carbon::setTestNow($failureAt->copy()->addHours($resolutionHours));
        $ticket->transitionTo(TicketStatus::Resolved, $technician->id);

        $result = $this->faker->boolean(12)
            ? TicketClosureResult::SolvedWithRecommendations
            : TicketClosureResult::Solved;

        Carbon::setTestNow($failureAt->copy()->addHours($resolutionHours + $this->faker->numberBetween(1, 24)));

        $ticket->close(
            $this->faker->randomElement([
                'Se reemplazó el componente dañado y se verificó el funcionamiento.',
                'Se ajustó el mecanismo y se realizó prueba de operación.',
                'Se corrigió la conexión eléctrica y se restableció el servicio.',
                'Se limpió el sistema y se reemplazó el lubricante.',
                'Se reparó la fuga y se presurizó el circuito.',
            ]),
            $result,
            $result === TicketClosureResult::SolvedWithRecommendations
                ? 'Se recomienda inspección en el próximo mantenimiento preventivo.'
                : null,
            $users['chief']->id,
        );
    }

    private function priorityFor(string $criticality): MaintenancePriority
    {
        $weights = match ($criticality) {
            'critical' => [
                MaintenancePriority::Critical->value => 20,
                MaintenancePriority::High->value => 40,
                MaintenancePriority::Medium->value => 30,
                MaintenancePriority::Low->value => 10,
            ],
            'high' => [
                MaintenancePriority::Critical->value => 10,
                MaintenancePriority::High->value => 35,
                MaintenancePriority::Medium->value => 40,
                MaintenancePriority::Low->value => 15,
            ],
            'medium' => [
                MaintenancePriority::Critical->value => 4,
                MaintenancePriority::High->value => 21,
                MaintenancePriority::Medium->value => 45,
                MaintenancePriority::Low->value => 30,
            ],
            default => [
                MaintenancePriority::Critical->value => 2,
                MaintenancePriority::High->value => 13,
                MaintenancePriority::Medium->value => 35,
                MaintenancePriority::Low->value => 50,
            ],
        };

        return MaintenancePriority::from($this->weightedChoice($weights));
    }

    /**
     * @param  array<string, int>  $weights
     */
    private function weightedChoice(array $weights): string
    {
        $roll = $this->faker->numberBetween(1, array_sum($weights));

        foreach ($weights as $value => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $value;
            }
        }

        return (string) array_key_first($weights);
    }
}
