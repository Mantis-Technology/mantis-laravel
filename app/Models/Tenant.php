<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

#[Fillable([
    'id',
    'name',
    'meta',
])]
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'meta',
        ];
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (blank($tenant->id) && filled($tenant->name)) {
                $tenant->id = Str::slug($tenant->name);
            }
        });

        static::created(function (Tenant $tenant) {
            $tenant->domains()->create([
                'domain' => "{$tenant->id}.localhost",
            ]);
        });

        static::updating(function (Tenant $tenant) {
            if (! $tenant->isDirty('id')) {
                return;
            }

            $domain = "{$tenant->id}.localhost";

            $occupied = Domain::query()
                ->where('domain', $domain)
                ->where('tenant_id', '!=', $tenant->getKey())
                ->exists();

            if ($occupied) {
                throw new \Stancl\Tenancy\Exceptions\DomainOccupiedByOtherTenantException(
                    $domain
                );
            }
        });

        static::updated(function (Tenant $tenant) {
            if (! $tenant->wasChanged('id')) {
                return;
            }

            $tenant->domains()->update([
                'domain' => "{$tenant->id}.localhost",
            ]);
        });
    }
}
