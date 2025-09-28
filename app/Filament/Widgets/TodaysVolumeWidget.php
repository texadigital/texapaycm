<?php

namespace App\Filament\Widgets;

use App\Models\DailyTransactionSummary;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodaysVolumeWidget extends BaseWidget
{
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $today = Carbon::today();

        $query = DailyTransactionSummary::query()->whereDate('transaction_date', $today);

        $totalAmount = (int) (clone $query)->sum('total_amount_xaf');
        $totalCount = (int) (clone $query)->sum('transaction_count');
        $successfulAmount = (int) (clone $query)->sum('successful_amount_xaf');
        $successfulCount = (int) (clone $query)->sum('successful_count');

        return [
            Stat::make("Today's Total Amount (XAF)", number_format($totalAmount))
                ->description('All transactions created today')
                ->color('primary'),
            Stat::make("Today's Successful Amount (XAF)", number_format($successfulAmount))
                ->description('Successful transactions today')
                ->color('success'),
            Stat::make("Today's Transactions", number_format($totalCount))
                ->description($successfulCount . ' successful')
                ->color('warning'),
        ];
    }
}
