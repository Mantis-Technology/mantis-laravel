<?php

namespace App\Filament\Resources\Tenants\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Enums\TenantStatus;
use Filament\Actions\Action; 

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('meta.image')
                    ->label('Logo')
                    ->circular()
                    ->imageSize(40),

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

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => $state->color()),


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

                Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== TenantStatus::Active && $record->status !== TenantStatus::Inactive)
                    ->action(fn ($record) => $record->activate()),

                Action::make('suspend')
                    ->label('Suspender')
                    ->icon('heroicon-o-pause-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === TenantStatus::Active)
                    ->action(fn ($record) => $record->suspend()),

                Action::make('deactivate')
                    ->label('Desactivar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status !== TenantStatus::Inactive)
                    ->action(fn ($record) => $record->deactivate()),
            ])

                        ->toolbarActions([]);
    }
}
