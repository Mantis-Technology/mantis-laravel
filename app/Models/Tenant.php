<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Exceptions\DomainOccupiedByOtherTenantException;

#[Fillable([
    'id',
    'name',
    'meta',
    'status',
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
            'status',
        ];
    }

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'data' => 'array',
            'status' => TenantStatus::class,
        ];
    }

    public function activate(): void
    {
        if ($this->status === TenantStatus::Inactive) {
            throw new \RuntimeException('An inactive tenant cannot be reactivated.');
        }

        if ($this->status === TenantStatus::Active) {
            throw new \RuntimeException('Tenant is already active.');
        }

        $this->update(['status' => TenantStatus::Active]);
    }

    public function suspend(): void
    {
        if ($this->status !== TenantStatus::Active) {
            throw new \RuntimeException('Only an active tenant can be suspended.');
        }

        $this->update(['status' => TenantStatus::Suspended]);
    }

    public function deactivate(): void
    {
        if ($this->status === TenantStatus::Inactive) {
            throw new \RuntimeException('Tenant is already inactive.');
        }

        $this->update(['status' => TenantStatus::Inactive]);
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (blank($tenant->id) && filled($tenant->name)) {
                $tenant->id = Str::slug($tenant->name);
            }

            if (blank($tenant->status)) {
                $tenant->status = TenantStatus::Pending->value;
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
                throw new DomainOccupiedByOtherTenantException(
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
