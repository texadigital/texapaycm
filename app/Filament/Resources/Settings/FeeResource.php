<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\FeeResource\Pages;
use App\Models\Settings\Fee;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class FeeResource extends Resource
{
    protected static ?string $model = Fee::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Fees & Pricing';
    protected static ?string $navigationLabel = 'Fees';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('corridor')->required()->maxLength(32)->placeholder('XAF_NGN'),
            Forms\Components\TextInput::make('min_xaf')->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('max_xaf')->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('flat_xaf')->numeric()->minValue(0)->required(),
            Forms\Components\TextInput::make('percent_bps')->numeric()->minValue(0)->maxValue(10000)->required(),
            Forms\Components\TextInput::make('cap_xaf')->numeric()->minValue(0)->required(),
            Forms\Components\Toggle::make('active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('corridor')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('min_xaf')->sortable(),
                Tables\Columns\TextColumn::make('max_xaf')->sortable(),
                Tables\Columns\TextColumn::make('flat_xaf')->sortable(),
                Tables\Columns\TextColumn::make('percent_bps')->label('Bps')->sortable(),
                Tables\Columns\TextColumn::make('cap_xaf')->sortable(),
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFees::route('/'),
            'create' => Pages\CreateFee::route('/create'),
            'edit' => Pages\EditFee::route('/{record}/edit'),
        ];
    }
}
