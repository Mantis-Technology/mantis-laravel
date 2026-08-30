<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->label('Nombre de la empresa')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $state, callable $set) {
                        $set('id', Str::slug($state));
                    }),

                TextInput::make('id')
                    ->label('Subdominio')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled()
                    ->dehydrated(),

                Section::make('Logo y descripción')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        FileUpload::make('meta.image')
                            ->label('Logo')
                            ->image()
                            ->disk('local')
                            ->directory('tenants/logos')
                            ->maxSize(10024),

                        Textarea::make('meta.description')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(255),
                    ]),

                Section::make('Datos fiscales')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tax_id')
                            ->label('NIF / CIF')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('contact_name')
                            ->label('Persona de contacto')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('contact_email')
                            ->label('Email de contacto')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(30),
                    ]),

                Section::make('Domicilio')
                    ->columns(2)
                    ->schema([
                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('city')
                            ->label('Ciudad')
                            ->maxLength(255),

                        TextInput::make('province')
                            ->label('Provincia')
                            ->maxLength(255),

                        TextInput::make('postal_code')
                            ->label('Código postal')
                            ->maxLength(10),

                        TextInput::make('country')
                            ->label('País')
                            ->maxLength(120),
                    ]),
            ]);
    }
}
