<?php

namespace App\Filament\Resources\Tenants\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('meta.name')
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

                FileUpload::make('meta.image')
                    ->label('Logo')
                    ->columnSpanFull()
                    ->image()
                    ->directory('tenants/logos')
                    ->maxSize(1024)
                    ->imagePreviewHeight('100')
                    ->automaticallyCropImagesToAspectRatio('1:1')
                    ->automaticallyResizeImagesToWidth('100')
                    ->automaticallyResizeImagesToHeight('100'),

                Textarea::make('meta.description')
                    ->label('Descripción')
                    ->columnSpanFull()
                    ->rows(3)
                    ->maxLength(255),
            ]);
    }
}
