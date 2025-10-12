<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmlAlertResource\Pages;
use App\Models\AmlAlert;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AmlAlertResource extends Resource
{
    protected static ?string $model = AmlAlert::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static string|\UnitEnum|null $navigationGroup = 'Limits & Risk';
    protected static ?int $navigationSort = 550;
    protected static ?string $navigationLabel = 'AML Alerts';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('status')->options([
                'open' => 'Open', 'investigating' => 'Investigating', 'closed' => 'Closed',
            ])->required(),
            Forms\Components\Textarea::make('notes')->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('transfer_id')->label('Transfer')->copyable()->toggleable(),
                Tables\Columns\BadgeColumn::make('severity')->colors([
                    'success' => 'low', 'warning' => 'medium', 'danger' => 'high', 'gray' => 'critical',
                ])->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'danger' => 'open', 'warning' => 'investigating', 'success' => 'closed',
                ])->sortable(),
                Tables\Columns\TextColumn::make('rule_key')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i')->since()->label('Created'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'open' => 'Open', 'investigating' => 'Investigating', 'closed' => 'Closed',
                ]),
                Tables\Filters\SelectFilter::make('severity')->options([
                    'low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical',
                ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
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
            'index' => Pages\ListAmlAlerts::route('/'),
            'view' => Pages\ViewAmlAlert::route('/{record}'),
            'edit' => Pages\EditAmlAlert::route('/{record}/edit'),
        ];
    }
}
