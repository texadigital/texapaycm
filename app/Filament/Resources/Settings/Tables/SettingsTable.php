<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->wrap(false),
                BadgeColumn::make('group')
                    ->colors([
                        'primary' => 'providers',
                        'success' => 'fees',
                        'warning' => 'fx',
                        'info' => 'limits',
                        'gray' => 'toggles',
                        'pink' => 'copy',
                        'secondary' => 'general',
                    ])
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                IconColumn::make('enabled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'providers' => 'Providers',
                        'fees' => 'Fees',
                        'fx' => 'FX',
                        'limits' => 'Limits',
                        'toggles' => 'Toggles',
                        'copy' => 'Copy/Labels',
                        'general' => 'General',
                    ]),
                TernaryFilter::make('enabled')
                    ->placeholder('All')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
