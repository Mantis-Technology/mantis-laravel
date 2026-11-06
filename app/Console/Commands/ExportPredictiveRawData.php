<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ExportPredictiveRawData extends Command
{
    protected $signature = 'predictive:export-raw
                            {--output= : Directorio de salida (por defecto data/raw)}';

    protected $description = 'Exporta las tablas operativas de todos los tenants a CSV para el ETL predictivo';

    /**
     * Tablas exportadas y sus columnas, en el orden del CSV. La clave es el
     * nombre del archivo y `table` permite apuntar a una tabla con otro nombre.
     *
     * @var array<string, array{columns: list<string>, primary: string, table?: string}>
     */
    private const TABLES = [
        'asset_cards' => [
            'columns' => ['id', 'asset_card_template_id', 'version', 'code', 'data', 'created_at', 'updated_at'],
            'primary' => 'id',
        ],
        'tickets' => [
            'columns' => [
                'id',
                'asset_card_id',
                'maintenance_category_id',
                'reported_by',
                'assigned_to',
                'location_id',
                'categorized_by',
                'title',
                'description',
                'maintenance_type',
                'priority',
                'status',
                'categorized_at',
                'created_at',
                'updated_at',
            ],
            'primary' => 'id',
        ],
        'ticket_closures' => [
            'columns' => ['id', 'ticket_id', 'work_performed', 'result', 'observations', 'closed_by', 'closed_at'],
            'primary' => 'id',
        ],
        'preventive_executions' => [
            'table' => 'preventive_maintenance_executions',
            'columns' => [
                'id',
                'preventive_maintenance_plan_id',
                'asset_card_id',
                'maintenance_category_id',
                'title',
                'scheduled_at',
                'original_scheduled_at',
                'assigned_to_id',
                'status',
                'started_at',
                'completed_at',
                'completed_by_id',
                'execution_notes',
                'findings',
                'created_at',
                'updated_at',
            ],
            'primary' => 'id',
        ],
        'maintenance_categories' => [
            'columns' => ['id', 'parent_id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
            'primary' => 'id',
        ],
        'locations' => [
            'columns' => ['id', 'parent_id', 'name', 'description', 'is_active', 'created_at', 'updated_at'],
            'primary' => 'id',
        ],
        'service_levels' => [
            'columns' => [
                'id',
                'maintenance_category_id',
                'maintenance_type',
                'priority',
                'response_hours',
                'resolution_hours',
                'is_active',
                'created_at',
                'updated_at',
            ],
            'primary' => 'id',
        ],
    ];

    public function handle(): int
    {
        $output = (string) ($this->option('output') ?: base_path('data/raw'));

        if (! str_starts_with($output, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:[\\\\\\/]/', $output)) {
            $output = base_path($output);
        }

        File::ensureDirectoryExists($output);

        $handles = [];

        foreach (array_keys(self::TABLES) as $table) {
            $path = $output.DIRECTORY_SEPARATOR.$table.'.csv';
            $handle = fopen($path, 'wb');

            if ($handle === false) {
                throw new RuntimeException("No se pudo abrir el archivo [{$path}] para escritura.");
            }

            fputcsv($handle, ['tenant_id', ...self::TABLES[$table]['columns']], ',', '"', '');
            $handles[$table] = $handle;

            $this->line("Exportando {$path}");
        }

        $counts = array_fill_keys(array_keys(self::TABLES), 0);

        try {
            Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($handles, &$counts): void {
                tenancy()->initialize($tenant);

                try {
                    foreach (self::TABLES as $table => $definition) {
                        $databaseTable = $definition['table'] ?? $table;

                        if (! DB::connection()->getSchemaBuilder()->hasTable($databaseTable)) {
                            continue;
                        }

                        DB::connection()->table($databaseTable)
                            ->orderBy($definition['primary'])
                            ->select($definition['columns'])
                            ->chunk(1000, function ($rows) use ($handles, $table, $tenant, &$counts): void {
                                foreach ($rows as $row) {
                                    $values = array_map(
                                        fn (mixed $value): mixed => is_bool($value) ? (int) $value : $value,
                                        array_values((array) $row),
                                    );

                                    fputcsv($handles[$table], [$tenant->getKey(), ...$values], ',', '"', '');
                                    $counts[$table]++;
                                }
                            });
                    }
                } finally {
                    tenancy()->end();
                }
            });
        } finally {
            foreach ($handles as $handle) {
                fclose($handle);
            }
        }

        $this->newLine();
        $this->table(
            ['Tabla', 'Filas'],
            collect($counts)->map(fn (int $count, string $table): array => [$table, $count])->values()->all(),
        );

        $this->info('Exportación completada en '.$output.'.');

        return self::SUCCESS;
    }
}
