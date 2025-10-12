<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\NotificationTemplateResource\Pages;
use App\Models\Settings\NotificationTemplate;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';
    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';
    protected static ?int $navigationSort = 710;
    protected static ?string $navigationLabel = 'Templates';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('event_key')->required()->maxLength(128)->placeholder('transfer.quote.created'),
            Forms\Components\Select::make('channel')->options([
                'email' => 'Email',
                'sms' => 'SMS',
                'push' => 'Push',
            ])->required()->native(false),
            Forms\Components\TextInput::make('subject')->maxLength(200),
            Forms\Components\Textarea::make('template')->rows(10)->required()->columnSpanFull(),
            Forms\Components\Toggle::make('enabled')->default(true),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('event_key')->label('Event Key')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('channel')->label('Channel')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->label('Enabled')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i')->since()->label('Updated')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')->boolean(),
                Tables\Filters\SelectFilter::make('channel')->options([
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'push' => 'Push',
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
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
