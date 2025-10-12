<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebhookEventResource\Pages;
use App\Models\WebhookEvent;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class WebhookEventResource extends Resource
{
    protected static ?string $model = WebhookEvent::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-wifi';
    protected static string|\UnitEnum|null $navigationGroup = 'Notifications';
    protected static ?int $navigationSort = 720;
    protected static ?string $navigationLabel = 'Webhook Events';

    public static function form(Schema $schema): Schema
    {
        // Read-only
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('provider')->label('Provider')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->label('Type')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('event_id')->label('Event ID')->limit(24)->tooltip(fn ($state) => $state)->toggleable(),
                Tables\Columns\TextColumn::make('signature_hash')->label('Signature')->limit(24)->tooltip(fn ($state) => $state)->toggleable(),
                Tables\Columns\TextColumn::make('processed_at')->dateTime('Y-m-d H:i')->since()->label('Processed')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i')->since()->label('Created')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')->options([
                    'pawapay' => 'PawaPay',
                ]),
                Tables\Filters\SelectFilter::make('type')->options([]),
            ])
            ->actions([
                Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWebhookEvents::route('/'),
            'view' => Pages\ViewWebhookEvent::route('/{record}'),
        ];
    }
}
