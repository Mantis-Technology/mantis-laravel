<?php

namespace App\Filament\Tenant\Resources\MaintenanceCategories\Pages;

use App\Filament\Tenant\Resources\MaintenanceCategories\MaintenanceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceCategories extends ListRecords
{
    protected static string $resource = MaintenanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
