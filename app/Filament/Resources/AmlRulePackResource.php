<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmlRulePackResource\Pages;
use App\Models\AmlRulePack;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AmlRulePackResource extends Resource
{
    protected static ?string $model = AmlRulePack::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';
    protected static ?string $navigationLabel = 'Rule Packs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord:true),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description')->rows(3),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\TagsInput::make('tags')->placeholder('Add tag'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->since(),
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListAmlRulePacks::route('/'),
            'create' => Pages\CreateAmlRulePack::route('/create'),
            'edit' => Pages\EditAmlRulePack::route('/{record}/edit'),
        ];
    }
}
