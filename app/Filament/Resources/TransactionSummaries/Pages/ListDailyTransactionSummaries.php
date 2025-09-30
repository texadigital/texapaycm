<?php

namespace App\Filament\Resources\TransactionSummaries\Pages;

use App\Filament\Resources\TransactionSummaries\DailyTransactionSummaryResource;
use Filament\Resources\Pages\ListRecords;

class ListDailyTransactionSummaries extends ListRecords
{
    protected static string $resource = DailyTransactionSummaryResource::class;
}
