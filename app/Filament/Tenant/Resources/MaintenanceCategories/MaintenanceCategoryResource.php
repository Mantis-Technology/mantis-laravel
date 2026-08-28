<?php

namespace App\Filament\Tenant\Resources\MaintenanceCategories;

use App\Filament\Tenant\Resources\MaintenanceCategories\Pages\CreateMaintenanceCategory;
use App\Filament\Tenant\Resources\MaintenanceCategories\Pages\EditMaintenanceCategory;
use App\Filament\Tenant\Resources\MaintenanceCategories\Pages\ListMaintenanceCategories;
use App\Filament\Tenant\Resources\MaintenanceCategories\Schemas\MaintenanceCategoryForm;
use App\Filament\Tenant\Resources\MaintenanceCategories\Tables\MaintenanceCategoriesTable;
use App\Models\MaintenanceCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceCategoryResource extends Resource
{
    protected static ?string $model = MaintenanceCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceCategories::route('/'),
            'create' => CreateMaintenanceCategory::route('/create'),
            'edit' => EditMaintenanceCategory::route('/{record}/edit'),
        ];
    }
}
