<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;

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
                            ->columnSpanFull()
                            ->imageHeight(200),

                        TextEntry::make('meta.description')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Datos fiscales y contacto')
                    ->schema([
                        TextEntry::make('tax_id')
                            ->label('NIF / CIF')
                            ->placeholder('-'),

                        TextEntry::make('contact_name')
                            ->label('Persona de contacto')
                            ->placeholder('-'),

                        TextEntry::make('contact_email')
                            ->label('Email de contacto')
                            ->placeholder('-'),

                        TextEntry::make('phone')
                            ->label('Teléfono')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Domicilio')
                    ->schema([
                        TextEntry::make('address')
                            ->label('Dirección')
                            ->placeholder('-'),

                        TextEntry::make('city')
                            ->label('Ciudad')
                            ->placeholder('-'),

                        TextEntry::make('province')
                            ->label('Provincia')
                            ->placeholder('-'),

                        TextEntry::make('postal_code')
                            ->label('Código postal')
                            ->placeholder('-'),

                        TextEntry::make('country')
                            ->label('País')
                            ->placeholder('-'),
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
