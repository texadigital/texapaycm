<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use App\Models\UserLimit;

class RecentCriticalUtilizationWidget extends BaseWidget
{
    protected static ?string $heading = 'Critical Utilization (≥95% Today)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today()->toDateString();

        $pctExpr = '(COALESCE(dts.total_amount_xaf, 0) * 100.0 / user_limits.daily_limit_xaf)';

        $query = UserLimit::query()
            ->from('user_limits')
            ->join('users as u', 'u.id', '=', 'user_limits.user_id')
            ->leftJoin('daily_transaction_summaries as dts', function ($join) use ($today) {
                $join->on('dts.user_id', '=', 'user_limits.user_id')
                    ->where('dts.transaction_date', '=', $today);
            })
            ->whereRaw('user_limits.daily_limit_xaf > 0')
            ->whereRaw("$pctExpr >= 95")
            ->selectRaw('user_limits.*, u.name, u.email, COALESCE(dts.total_amount_xaf, 0) as used_amount, '
                . "$pctExpr as pct")
            ->orderByDesc(DB::raw($pctExpr))
            ->limit(10);

        return $table
            ->paginated(false)
            ->query(fn () => $query)
            ->columns([
                TextColumn::make('name')->label('User')->searchable(),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('used_amount')->label('Used Today (XAF)')->numeric(),
                TextColumn::make('daily_limit_xaf')->label('Daily Limit (XAF)')->numeric(),
                TextColumn::make('pct')->label('Utilization %')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1) . '%')
                    ->badge()
                    ->color('danger'),
            ]);
    }
}
