<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use App\Models\UserLimit;

class NearLimitUsersWidget extends BaseWidget
{
    protected static ?string $heading = 'Users Near Daily Limits';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $today = Carbon::today()->toDateString();

        // Build a query (not a collection) for the table
        $amountPctExpr = "CASE WHEN user_limits.daily_limit_xaf > 0 THEN (COALESCE(dts.total_amount_xaf,0) * 100.0 / user_limits.daily_limit_xaf) ELSE 0 END";
        $countPctExpr = "CASE WHEN user_limits.daily_count_limit > 0 THEN (COALESCE(dts.transaction_count,0) * 100.0 / user_limits.daily_count_limit) ELSE 0 END";
        $utilPctExpr = "CASE WHEN ($amountPctExpr) >= ($countPctExpr) THEN ($amountPctExpr) ELSE ($countPctExpr) END";

        $query = UserLimit::query()
            ->from('user_limits')
            ->join('users as u', 'u.id', '=', 'user_limits.user_id')
            ->leftJoin('daily_transaction_summaries as dts', function ($join) use ($today) {
                $join->on('dts.user_id', '=', 'user_limits.user_id')
                    ->where('dts.transaction_date', '=', $today);
            })
            ->selectRaw('user_limits.*, u.name, u.email, COALESCE(dts.total_amount_xaf, 0) as used_amount, COALESCE(dts.transaction_count, 0) as used_count, '
                . "$amountPctExpr as amount_pct, $countPctExpr as count_pct, $utilPctExpr as utilization_pct")
            ->whereRaw("$utilPctExpr >= 80")
            ->orderByDesc(DB::raw($utilPctExpr))
            ->limit(10);

        return $table
            ->paginated(false)
            ->query(fn () => $query)
            ->columns([
                TextColumn::make('name')->label('User')->searchable(),
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('used_amount')->label('Used Today (XAF)')->numeric(),
                TextColumn::make('daily_limit_xaf')->label('Daily Limit (XAF)')->numeric(),
                TextColumn::make('used_count')->label('Tx Count Today')->numeric()->toggleable(),
                TextColumn::make('daily_count_limit')->label('Daily Count Limit')->numeric()->toggleable(),
                TextColumn::make('utilization_pct')->label('Max Utilization %')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, 1) . '%')
                    ->badge()
                    ->color(fn ($state) => ((float) $state) >= 95 ? 'danger' : 'warning'),
            ]);
    }
}
