<?php

namespace App\Filament\Resources\UserLimits\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserLimitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User')
                    ->schema([
                        TextInput::make('user_info')
                            ->label('User')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(function ($state, $record) {
                                if (!$record || !$record->user) {
                                    return '';
                                }
                                return $record->user->name . ' (' . $record->user->email . ')';
                            }),
                    ]),
                Section::make('Limits')
                    ->schema([
                        TextInput::make('daily_limit_xaf')
                            ->label('Daily Limit (XAF)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('monthly_limit_xaf')
                            ->label('Monthly Limit (XAF)')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('daily_count_limit')
                            ->label('Daily Count Limit')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('monthly_count_limit')
                            ->label('Monthly Count Limit')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('When disabled, transactions are blocked for this user.'),
                        TextInput::make('notes')
                            ->label('Admin Notes')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
