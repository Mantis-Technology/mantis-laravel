<?php

namespace App\Models;

use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $fillable = [
        'id',
        'data',
        'meta',
    ];

    public function metadata(): array
    {
        return $this->data->meta ?? [];
    }

    public function name(): string
    {
        return $this->data->meta->name ?? '';
    }

    public function imageMetadata(): array
    {
        return $this->data->meta->image ?? [];
    }

    protected static function booted(): void
    {
        static::creating(function (Tenant $tenant) {
            if (blank($tenant->id) && filled($tenant->name)) {
                $tenant->id = Str::slug($tenant->name);
            }
        });
    }
}
