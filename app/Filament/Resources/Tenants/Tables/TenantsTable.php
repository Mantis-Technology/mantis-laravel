<?php

namespace App\Filament\Resources\Tenants\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('meta.image')
                    ->label('Logo')
                    ->circular()
                    ->size(40),

                TextColumn::make('name')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('id')
                    ->label('Subdominio')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Subdominio copiado'),

                TextColumn::make('meta.description')
                    ->label('Descripción')
                    ->limit(50)
                    ->tooltip(fn (TextColumn $column): ?string => $column->getState())
                    ->placeholder('Sin descripción')
                    ->toggleable(),

                TextColumn::make('domains.domain')
                    ->label('Dominio')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Dominio copiado')
                    ->placeholder('Sin dominio'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}