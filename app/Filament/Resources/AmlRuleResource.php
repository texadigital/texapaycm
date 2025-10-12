<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmlRuleResource\Pages;
use App\Models\AmlRule;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AmlRuleResource extends Resource
{
    protected static ?string $model = AmlRule::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-queue-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Limits & Risk';
    protected static ?int $navigationSort = 530;
    protected static ?string $navigationLabel = 'AML Rules';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')->required()->unique(ignoreRecord:true),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\Textarea::make('description')->rows(3),
            Forms\Components\Select::make('severity')->options([
                'low' => 'Low','medium' => 'Medium','high' => 'High','critical' => 'Critical',
            ])->default('medium')->required(),
            Forms\Components\Toggle::make('is_active')->default(true),
            Forms\Components\KeyValue::make('expression')->keyLabel('Key')->valueLabel('Value')->addButtonLabel('Add'),
            Forms\Components\KeyValue::make('thresholds')->keyLabel('Key')->valueLabel('Value')->addButtonLabel('Add'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('key')->searchable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\BadgeColumn::make('severity')->colors([
                    'success' => 'low','warning' => 'medium','danger' => 'high','gray' => 'critical',
                ])->sortable(),
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
            'index' => Pages\ListAmlRules::route('/'),
            'create' => Pages\CreateAmlRule::route('/create'),
            'edit' => Pages\EditAmlRule::route('/{record}/edit'),
        ];
    }
}
