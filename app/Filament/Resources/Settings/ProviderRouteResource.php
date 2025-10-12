<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\ProviderRouteResource\Pages;
use App\Models\Settings\ProviderRoute;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderRouteResource extends Resource
{
    protected static ?string $model = ProviderRoute::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null $navigationGroup = 'Providers';
    protected static ?int $navigationSort = 610;
    protected static ?string $navigationLabel = 'Provider Routes';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('corridor')->required()->maxLength(32)->placeholder('XAF_NGN'),
            Forms\Components\TextInput::make('provider_code')->required()->maxLength(64),
            Forms\Components\TextInput::make('weight')->numeric()->minValue(0)->maxValue(10000)->default(100),
            Forms\Components\KeyValue::make('msisdn_prefixes')->keyLabel('index')->valueLabel('prefix')->helperText('array of prefixes or ranges'),
            Forms\Components\Toggle::make('active')->default(true),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('corridor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('provider_code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('weight')->sortable(),
                Tables\Columns\IconColumn::make('active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')->boolean(),
                Tables\Filters\SelectFilter::make('corridor')->options([
                    'XAF_NGN' => 'XAF_NGN',
                ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProviderRoutes::route('/'),
            'create' => Pages\CreateProviderRoute::route('/create'),
            'edit' => Pages\EditProviderRoute::route('/{record}/edit'),
        ];
    }
}
