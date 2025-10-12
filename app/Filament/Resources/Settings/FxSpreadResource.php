<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\FxSpreadResource\Pages;
use App\Models\Settings\FxSpread;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FxSpreadResource extends Resource
{
    protected static ?string $model = FxSpread::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string|\UnitEnum|null $navigationGroup = 'Pricing';
    protected static ?int $navigationSort = 420;
    protected static ?string $navigationLabel = 'FX Spreads';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('corridor')->required()->maxLength(32)->placeholder('XAF_NGN'),
            Forms\Components\TextInput::make('provider')->maxLength(64),
            Forms\Components\TextInput::make('margin_bps')->numeric()->minValue(0)->maxValue(10000)->required(),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('corridor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('provider')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('margin_bps')->label('Bps')->sortable(),
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
            'index' => Pages\ListFxSpreads::route('/'),
            'create' => Pages\CreateFxSpread::route('/create'),
            'edit' => Pages\EditFxSpread::route('/{record}/edit'),
        ];
    }
}

