<?php

namespace App\Filament\Resources\Transfers;

use App\Filament\Resources\Transfers\Pages\CreateTransfer;
use App\Filament\Resources\Transfers\Pages\EditTransfer;
use App\Filament\Resources\Transfers\Pages\ListTransfers;
use App\Filament\Resources\Transfers\Schemas\TransferForm;
use App\Filament\Resources\Transfers\Tables\TransfersTable;
use App\Models\Transfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Infolists\Components\TextEntry;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = 'Transactions';
    protected static ?int $navigationSort = 310;
    public static function form(Schema $schema): Schema
    {
        return TransferForm::configure($schema);
    }
    public static function table(Table $table): Table
    {
        return TransfersTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            TextEntry::make('id')->label('ID'),
            TextEntry::make('user_id')->label('User ID'),
            TextEntry::make('payin_ref')->label('Pay-in Ref'),
            TextEntry::make('payout_ref')->label('Payout Ref'),
            TextEntry::make('payin_status')->label('Pay-in Status')->badge(),
            TextEntry::make('payout_status')->label('Payout Status')->badge(),
            TextEntry::make('status')->label('Overall Status')->badge(),
            TextEntry::make('amount_xaf')->label('Amount XAF'),
            TextEntry::make('receive_ngn_minor')->label('Receive NGN (minor)'),
            TextEntry::make('recipient_bank_name')->label('Bank'),
            TextEntry::make('recipient_account_number')->label('Account #'),
            TextEntry::make('created_at')->dateTime()->since(),
            TextEntry::make('updated_at')->dateTime()->since(),
        ])->columns(2);
    }

    public static function getRelations(): array
    {
        return [];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListTransfers::route('/'),
            'create' => CreateTransfer::route('/create'),
            'view' => \App\Filament\Resources\Transfers\Pages\ViewTransfer::route('/{record}'),
            'edit' => EditTransfer::route('/{record}/edit'),
        ];
    }

}
