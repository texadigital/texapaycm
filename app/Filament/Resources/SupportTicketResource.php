<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupportTicketResource\Pages;
use App\Models\SupportTicket;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    protected static ?string $model = SupportTicket::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-lifebuoy';
    protected static string|\UnitEnum|null $navigationGroup = 'Support';
    protected static ?string $navigationLabel = 'Support Tickets';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('subject')->required()->maxLength(190),
            Forms\Components\Textarea::make('message')->rows(8)->required(),
            Forms\Components\Select::make('priority')->options([
                'low' => 'Low', 'normal' => 'Normal', 'high' => 'High',
            ])->default('normal')->required(),
            Forms\Components\Select::make('status')->options([
                'open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed',
            ])->default('open')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('subject')->searchable()->wrap(),
                Tables\Columns\BadgeColumn::make('priority')->colors([
                    'success' => 'low', 'warning' => 'normal', 'danger' => 'high',
                ]),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'closed', 'warning' => 'pending', 'danger' => 'open',
                ]),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'open' => 'Open', 'pending' => 'Pending', 'closed' => 'Closed',
                ]),
                Tables\Filters\SelectFilter::make('priority')->options([
                    'low' => 'Low', 'normal' => 'Normal', 'high' => 'High',
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
            'index' => Pages\ListSupportTickets::route('/'),
            'view' => Pages\ViewSupportTicket::route('/{record}'),
            'edit' => Pages\EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
