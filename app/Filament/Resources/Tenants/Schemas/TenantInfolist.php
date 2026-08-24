<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Illuminate\Contracts\Support\Htmlable;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la empresa')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre de la empresa')
                            ->weight('bold')
                            ->size(TextSize::Large),

                        TextEntry::make('id')
                            ->label('Subdominio'),

                        ImageEntry::make('meta.image')
                            ->hiddenLabel()
                            ->disk('tenant_logos')
                            ->columnSpanFull()
                            ->imageHeight(200),

                        TextEntry::make('meta.description')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Información del sistema')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Creado')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),

                        TextEntry::make('updated_at')
                            ->label('Última actualización')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2),
            ]);
    }
}