<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Services\PredictiveDemo\GenerateTenantHistory;
use Illuminate\Database\Seeder;

class PredictiveTenantSeeder extends Seeder
{
    public function run(?string $tenantId = null): void
    {
        $tenantId ??= (string) tenant('id');

        /** @var Tenant $tenant */
        $tenant = Tenant::query()->findOrFail($tenantId);

        /** @var array<int, array<string, mixed>> $companies */
        $companies = config('predictive.demo.companies');

        /** @var array<string, mixed>|null $company */
        $company = collect($companies)->firstWhere('id', $tenantId);

        if ($company === null) {
            throw new \RuntimeException("No hay una empresa configurada para el tenant [{$tenantId}].");
        }

        tenancy()->initialize($tenant);

        try {
            app(GenerateTenantHistory::class)->execute($tenant, $company);
        } finally {
            tenancy()->end();
        }

        $this->command->info("Historial predictivo generado para {$tenant->name}.");
    }
}
