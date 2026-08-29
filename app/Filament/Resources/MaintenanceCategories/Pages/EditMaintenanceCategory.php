<?php

namespace App\Filament\Tenant\Resources\MaintenanceCategories\Pages;

use App\Filament\Tenant\Resources\MaintenanceCategories\MaintenanceCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceCategory extends EditRecord
{
    protected static string $resource = MaintenanceCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
