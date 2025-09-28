<?php

namespace App\Filament\Resources\AdminSettings\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdminSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Setting')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('setting_key')
                                ->label('Key')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Key is immutable.'),
                            Select::make('setting_type')
                                ->label('Type')
                                ->options([
                                    'string' => 'String',
                                    'integer' => 'Integer',
                                    'boolean' => 'Boolean',
                                    'json' => 'JSON',
                                ])
                                ->required(),
                        ]),
                        Grid::make(2)->schema([
                            TextInput::make('category')
                                ->label('Category')
                                ->required(),
                            Toggle::make('is_public')->label('Public'),
                        ]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2),
                        TextInput::make('setting_value')
                            ->label('Value')
                            ->columnSpanFull()
                            ->helperText('Enter raw value. Type casting is handled automatically.'),
                    ]),
            ]);
    }
}
