<?php

namespace App\Filament\Resources\AdminSettings\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AdminSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('setting_key')->label('Key')->searchable()->sortable(),
                TextColumn::make('setting_value')
                    ->label('Value')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->setting_type === 'boolean') {
                            return $state ? 'true' : 'false';
                        }
                        if ($record->setting_type === 'json') {
                            return is_string($state) ? $state : json_encode($state);
                        }
                        return (string) $state;
                    })
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('setting_type')->label('Type')->sortable(),
                TextColumn::make('category')->label('Category')->sortable(),
                IconColumn::make('is_public')->boolean()->label('Public')->sortable(),
                TextColumn::make('updated_at')->dateTime()->since()->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options([
                        'user_limits' => 'User Limits',
                        'security' => 'Security',
                        'system' => 'System',
                        'notifications' => 'Notifications',
                        'support' => 'Support',
                        'company' => 'Company',
                        'general' => 'General',
                    ])->label('Category'),
                TernaryFilter::make('is_public')->label('Public'),
            ]);
    }
}
