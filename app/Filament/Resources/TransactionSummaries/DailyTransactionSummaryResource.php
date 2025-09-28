<?php

namespace App\Filament\Resources\TransactionSummaries;

use App\Filament\Resources\TransactionSummaries\Pages\ListDailyTransactionSummaries;
use App\Filament\Resources\TransactionSummaries\Tables\DailyTransactionSummariesTable;
use App\Models\DailyTransactionSummary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DailyTransactionSummaryResource extends Resource
{
    protected static ?string $model = DailyTransactionSummary::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public static function form(Schema $schema): Schema
    {
        // Read-only resource
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return DailyTransactionSummariesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyTransactionSummaries::route('/'),
        ];
    }
}
