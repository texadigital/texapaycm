<?php

namespace App\Filament\Resources\TransactionSummaries\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DailyTransactionSummariesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('transaction_date')->date()->sortable()->label('Date'),
                TextColumn::make('total_amount_xaf')->numeric()->label('Total XAF')->sortable(),
                TextColumn::make('transaction_count')->numeric()->label('Count')->sortable(),
                TextColumn::make('successful_amount_xaf')->numeric()->label('Successful XAF')->sortable(),
                TextColumn::make('successful_count')->numeric()->label('Successful Cnt')->sortable(),
                TextColumn::make('created_at')->dateTime()->since()->label('Created')->toggleable(),
            ])
            ->filters([
                Filter::make('transaction_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('transaction_date', '<=', $date));
                    })
                    ->label('Date Between'),
                SelectFilter::make('user_id')
                    ->relationship('user', 'email')
                    ->label('User'),
            ]);
    }
}
