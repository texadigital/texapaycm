<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User')
                    ->schema([
                        TextInput::make('name')->label('Name')->required(),
                        TextInput::make('phone')->label('Phone')->disabled(),
                        TextInput::make('email')->label('Email')->disabled(),
                    ])->columns(3),
                Section::make('KYC')
                    ->schema([
                        ToggleButtons::make('kyc_status')
                            ->label('KYC Status')
                            ->options([
                                'unverified' => 'Unverified',
                                'pending' => 'Pending',
                                'verified' => 'Verified',
                                'failed' => 'Failed',
                            ])
                            ->colors([
                                'unverified' => 'gray',
                                'pending' => 'warning',
                                'verified' => 'success',
                                'failed' => 'danger',
                            ])
                            ->required(),
                        ToggleButtons::make('kyc_level')
                            ->label('KYC Level')
                            ->options([
                                0 => 'Level 0',
                                1 => 'Level 1',
                            ])
                            ->colors([
                                0 => 'gray',
                                1 => 'success',
                            ])
                            ->required(),
                        TextInput::make('kyc_provider_ref')->label('Provider Ref')->disabled(),
                        Textarea::make('kyc_meta')->label('KYC Meta')
                            ->helperText('JSON preview of last result')
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state)
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
