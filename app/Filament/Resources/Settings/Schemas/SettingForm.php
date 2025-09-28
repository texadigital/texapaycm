<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->maxLength(191),
                Select::make('group')
                    ->label('Group')
                    ->options([
                        'providers' => 'Providers',
                        'fees' => 'Fees',
                        'fx' => 'FX',
                        'limits' => 'Limits',
                        'toggles' => 'Toggles',
                        'copy' => 'Copy/Labels',
                        'general' => 'General',
                    ])
                    ->required(),
                Select::make('type')
                    ->options([
                        'string' => 'String',
                        'integer' => 'Integer',
                        'decimal' => 'Decimal',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ])
                    ->required(),
                Toggle::make('enabled')
                    ->label('Enabled')
                    ->default(true),
                TextInput::make('label')
                    ->label('Label')
                    ->maxLength(191),
                Textarea::make('description')
                    ->rows(2)
                    ->label('Description'),
                Textarea::make('value')
                    ->rows(6)
                    ->helperText('Enter the value according to the selected type. For JSON, provide valid JSON.'),
            ]);
    }
}
