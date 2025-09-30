<?php

namespace App\Filament\Resources\UserLimits\Tables;

use App\Models\AdminSetting;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UserLimitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable(),
                TextColumn::make('daily_limit_xaf')->label('Daily XAF')->numeric()->sortable(),
                TextColumn::make('monthly_limit_xaf')->label('Monthly XAF')->numeric()->sortable(),
                TextColumn::make('daily_count_limit')->label('Daily Cnt')->numeric()->sortable(),
                TextColumn::make('monthly_count_limit')->label('Monthly Cnt')->numeric()->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active')->sortable(),
                TextColumn::make('updated_at')->dateTime()->since()->label('Updated'),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                Filter::make('updated_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('updated_at', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('updated_at', '<=', $date));
                    })
                    ->label('Updated Between'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('enableLimits')
                        ->label('Enable Limits')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => true]);
                            }
                        }),
                    BulkAction::make('disableLimits')
                        ->label('Disable Limits')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->update(['is_active' => false]);
                            }
                        }),
                    BulkAction::make('applyDefaults')
                        ->label('Apply Default Limits')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $defaults = [
                                'daily_limit_xaf' => AdminSetting::getValue('default_daily_limit', 500000),
                                'monthly_limit_xaf' => AdminSetting::getValue('default_monthly_limit', 5000000),
                                'daily_count_limit' => AdminSetting::getValue('default_daily_count', 10),
                                'monthly_count_limit' => AdminSetting::getValue('default_monthly_count', 100),
                            ];
                            foreach ($records as $record) {
                                $record->update($defaults);
                            }
                        }),
                ]),
            ]);
    }
}
