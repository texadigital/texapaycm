<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScreeningResource\Pages;
use App\Models\ScreeningCheck;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ScreeningResource extends Resource
{
    protected static ?string $model = ScreeningCheck::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';
    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';
    protected static ?string $navigationLabel = 'Screening Checks';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('id')->disabled(),
            Forms\Components\TextInput::make('user_id')->disabled(),
            Forms\Components\TextInput::make('type')->disabled(),
            Forms\Components\TextInput::make('provider')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\DateTimePicker::make('completed_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\BadgeColumn::make('type')->colors([
                    'info' => 'kyc_update',
                    'warning' => 'login',
                    'success' => 'onboarding',
                    'gray' => 'periodic',
                ])->sortable(),
                Tables\Columns\TextColumn::make('provider')->toggleable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'running',
                    'success' => 'completed',
                    'danger' => 'failed',
                ])->sortable(),
                Tables\Columns\TextColumn::make('completed_at')->dateTime()->since()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'running' => 'Running',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                ]),
                Tables\Filters\SelectFilter::make('type')->options([
                    'onboarding' => 'Onboarding',
                    'kyc_update' => 'KYC Update',
                    'login' => 'Login',
                    'periodic' => 'Periodic',
                ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScreeningChecks::route('/'),
            'view' => Pages\ViewScreeningCheck::route('/{record}'),
        ];
    }
}
