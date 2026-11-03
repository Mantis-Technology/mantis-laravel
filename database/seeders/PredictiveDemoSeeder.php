<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PredictiveDemoSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array<string, mixed>> $companies */
        $companies = config('predictive.demo.companies');

        foreach ($companies as $company) {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::query()->find($company['id']);

            $attributes = [
                'name' => $company['name'],
                'tax_id' => $company['tax_id'],
                'contact_email' => $company['contact_email'],
                'phone' => $company['phone'],
                'contact_name' => $company['contact_name'],
                'address' => $company['address'],
                'city' => $company['city'],
                'province' => $company['province'],
                'postal_code' => $company['postal_code'],
                'country' => $company['country'],
            ];

            if ($tenant === null) {
                /** @var Tenant $tenant */
                $tenant = Tenant::query()->create([
                    'id' => $company['id'],
                    ...$attributes,
                ]);

                $this->command->info("Empresa creada: {$tenant->name} ({$tenant->id}).");
            } else {
                $tenant->fill($attributes);
                $tenant->save();

                $this->command->info("Empresa reutilizada: {$tenant->name} ({$tenant->id}).");
            }

            if ($tenant->getRawOriginal('status') === TenantStatus::Pending->value) {
                $tenant->activate();
            }

            $this->call(PredictiveTenantSeeder::class, false, [
                'tenantId' => (string) $tenant->getKey(),
            ]);
        }
    }
}
