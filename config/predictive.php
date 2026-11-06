<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Dataset de demostración para analítica predictiva
    |--------------------------------------------------------------------------
    |
    | Los valores de este bloque controlan el volumen y las fechas del set de
    | datos sintético que usa el seeder de demostración. El volumen por defecto
    | produce 4 empresas x 125 activos x 24 cortes = 12,000 filas de dataset.
    |
    */

    'demo' => [
        'seed' => (int) env('PREDICTIVE_DEMO_SEED', 20260101),

        'assets_per_tenant' => (int) env('PREDICTIVE_DEMO_ASSETS_PER_TENANT', 125),

        'cutoffs' => (int) env('PREDICTIVE_DEMO_CUTOFFS', 24),

        'history_start' => env('PREDICTIVE_DEMO_HISTORY_START', '2023-01-01'),

        'history_end' => env('PREDICTIVE_DEMO_HISTORY_END', '2026-01-31'),

        'cutoff_start' => env('PREDICTIVE_DEMO_CUTOFF_START', '2024-02-01'),

        'companies' => [
            [
                'id' => 'acero-norte',
                'name' => 'Acero Norte S.A.',
                'tax_id' => '1790012345001',
                'contact_email' => 'mantenimiento@acero-norte.test',
                'phone' => '022555101',
                'contact_name' => 'Andrea Salazar',
                'address' => 'Av. de los Metales 150',
                'city' => 'Quito',
                'province' => 'Pichincha',
                'postal_code' => '170101',
                'country' => 'Ecuador',
                'profile' => [
                    'asset_types' => [
                        'Compresor',
                        'Prensa hidráulica',
                        'Puente grúa',
                        'Bomba industrial',
                        'Motor eléctrico',
                        'Taladro industrial',
                    ],
                    'failure_categories' => [
                        'Eléctrica',
                        'Mecánica',
                        'Hidráulica',
                        'Neumática',
                        'Lubricación',
                    ],
                    'base_hazard' => 0.15,
                    'preventive_compliance' => 0.78,
                    'sites' => [
                        ['name' => 'Planta principal', 'zones' => ['Producción', 'Mantenimiento', 'Almacén']],
                        ['name' => 'Planta norte', 'zones' => ['Forja', 'Acabados', 'Logística']],
                        ['name' => 'Centro de servicios', 'zones' => ['Taller', 'Repuestos', 'Patio']],
                    ],
                ],
            ],
            [
                'id' => 'alimentos-valle',
                'name' => 'Alimentos del Valle S.A.',
                'tax_id' => '1790012345002',
                'contact_email' => 'operaciones@alimentos-valle.test',
                'phone' => '022555102',
                'contact_name' => 'María Paredes',
                'address' => 'Km. 12 Vía al Valle',
                'city' => 'Ambato',
                'province' => 'Tungurahua',
                'postal_code' => '180101',
                'country' => 'Ecuador',
                'profile' => [
                    'asset_types' => [
                        'Cámara frigorífica',
                        'Caldera',
                        'Transportador',
                        'Compresor',
                        'Bomba industrial',
                        'Envasadora',
                    ],
                    'failure_categories' => [
                        'Eléctrica',
                        'Mecánica',
                        'Hidráulica',
                        'Neumática',
                        'Instrumentación',
                    ],
                    'base_hazard' => 0.18,
                    'preventive_compliance' => 0.72,
                    'sites' => [
                        ['name' => 'Planta de producción', 'zones' => ['Envasado', 'Cámaras', 'Calderas']],
                        ['name' => 'Centro de distribución', 'zones' => ['Recepción', 'Despacho', 'Cámaras']],
                        ['name' => 'Planta de procesos', 'zones' => ['Mezclas', 'Limpieza', 'Calidad']],
                    ],
                ],
            ],
            [
                'id' => 'logistica-pacifico',
                'name' => 'Logística Pacífico S.A.',
                'tax_id' => '1790012345003',
                'contact_email' => 'flota@logistica-pacifico.test',
                'phone' => '022555103',
                'contact_name' => 'Carlos Mendoza',
                'address' => 'Av. Portuaria 405',
                'city' => 'Guayaquil',
                'province' => 'Guayas',
                'postal_code' => '090101',
                'country' => 'Ecuador',
                'profile' => [
                    'asset_types' => [
                        'Montacargas',
                        'Cinta transportadora',
                        'Grúa horquilla',
                        'Generador eléctrico',
                        'Compresor',
                        'Bomba industrial',
                    ],
                    'failure_categories' => [
                        'Eléctrica',
                        'Mecánica',
                        'Hidráulica',
                        'Neumática',
                        'Estructural',
                    ],
                    'base_hazard' => 0.20,
                    'preventive_compliance' => 0.65,
                    'sites' => [
                        ['name' => 'Patio de contenedores', 'zones' => ['Andén 1', 'Andén 2', 'Taller']],
                        ['name' => 'Centro logístico', 'zones' => ['Almacén A', 'Almacén B', 'Despacho']],
                        ['name' => 'Terminal terrestre', 'zones' => ['Acceso', 'Muelle', 'Mantenimiento']],
                    ],
                ],
            ],
            [
                'id' => 'textiles-andinos',
                'name' => 'Textiles Andinos S.A.',
                'tax_id' => '1790012345004',
                'contact_email' => 'planta@textiles-andinos.test',
                'phone' => '022555104',
                'contact_name' => 'Lucía Cárdenas',
                'address' => 'Calle de los Telares 88',
                'city' => 'Cuenca',
                'province' => 'Azuay',
                'postal_code' => '010101',
                'country' => 'Ecuador',
                'profile' => [
                    'asset_types' => [
                        'Telar',
                        'Motor eléctrico',
                        'Caldera',
                        'Compresor',
                        'Bomba industrial',
                        'Cortadora textil',
                    ],
                    'failure_categories' => [
                        'Eléctrica',
                        'Mecánica',
                        'Hidráulica',
                        'Neumática',
                        'Lubricación',
                    ],
                    'base_hazard' => 0.16,
                    'preventive_compliance' => 0.74,
                    'sites' => [
                        ['name' => 'Planta de tejido', 'zones' => ['Telares', 'Hilatura', 'Acabados']],
                        ['name' => 'Planta de confección', 'zones' => ['Corte', 'Costura', 'Empaque']],
                        ['name' => 'Centro de mantenimiento', 'zones' => ['Taller', 'Bodega', 'Calderas']],
                    ],
                ],
            ],
        ],
    ],

];
