<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProtectedTransactionResource\Pages;
use App\Models\ProtectedTransaction;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ProtectedTransactionResource extends Resource
{
    protected static ?string $model = ProtectedTransaction::class;

    // Navigation props omitted to avoid type mismatch; Filament defaults will apply
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = 'Protected (Escrow)';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('funding_ref')->label('Ref')->copyable()->searchable(),
                TextColumn::make('buyer_user_id')->label('Buyer ID')->sortable(),
                TextColumn::make('receiver_account_number')->label('Receiver')->searchable(),
                TextColumn::make('amount_ngn_minor')->label('Amount (kobo)')->sortable(),
                TextColumn::make('fee_ngn_minor')->label('Fee (kobo)')->sortable(),
                TextColumn::make('escrow_state')->badge()->sortable()->colors([
                    'warning' => 'awaiting_approval',
                    'success' => 'released',
                    'danger' => 'disputed',
                    'gray' => 'created',
                ]),
                TextColumn::make('auto_release_at')->dateTime()->label('Auto Release'),
                TextColumn::make('updated_at')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('escrow_state')->options([
                    'created' => 'created',
                    'awaiting_approval' => 'awaiting_approval',
                    'released' => 'released',
                    'disputed' => 'disputed',
                ]),
            ])
            ->recordUrl(fn(ProtectedTransaction $r) => Pages\ViewProtectedTransaction::getUrl([$r->getKeyName() => $r->getKey()]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProtectedTransactions::route('/'),
            'view' => Pages\ViewProtectedTransaction::route('/{record}'),
        ];
    }
}
