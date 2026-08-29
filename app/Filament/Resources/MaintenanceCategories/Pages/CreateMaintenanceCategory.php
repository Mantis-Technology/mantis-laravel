<?php

namespace App\Filament\Tenant\Resources\MaintenanceCategories\Pages;

use App\Filament\Tenant\Resources\MaintenanceCategories\MaintenanceCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceCategory extends CreateRecord
{
    protected static string $resource = MaintenanceCategoryResource::class;
}
