<?php

namespace App\Filament\Resources\Transfers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Table;

class TransfersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('payin_ref')->label('Pay-in Ref')->searchable()->toggleable(),
                TextColumn::make('payout_ref')->label('Payout Ref')->searchable()->toggleable(),
                BadgeColumn::make('payin_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                        'gray' => 'canceled',
                    ])->label('Pay-in'),
                BadgeColumn::make('payout_status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                    ])->label('Payout'),
                BadgeColumn::make('status')
                    ->colors([
                        'info' => 'quote_created',
                        'warning' => 'payin_pending',
                        'success' => 'payout_success',
                        'gray' => 'payout_pending',
                        'danger' => 'failed',
                        'success' => 'payin_success',
                    ])->label('Overall'),
                TextColumn::make('amount_xaf')
                    ->label('Amount')
                    ->money('XAF', locale: 'en_CM')
                    ->sortable(),
                TextColumn::make('receive_ngn_minor')
                    ->label('NGN')
                    ->formatStateUsing(fn ($state) => is_numeric($state) ? number_format($state / 100, 2) : null)
                    ->suffix(' NGN')
                    ->alignRight()
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('recipient_bank_name')->label('Bank')->toggleable(),
                TextColumn::make('recipient_account_number')
                    ->label('Acct #')
                    ->formatStateUsing(fn ($state) => $state ? str_repeat('•', max(strlen($state) - 4, 0)) . substr($state, -4) : null)
                    ->toggleable(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->since()->sortable()->label('Created'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'quote_created' => 'Quote Created',
                        'payin_pending' => 'Pay-in Pending',
                        'payin_success' => 'Pay-in Success',
                        'payout_pending' => 'Payout Pending',
                        'payout_success' => 'Payout Success',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('payin_status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'canceled' => 'Canceled',
                    ])->label('Pay-in Status'),
                SelectFilter::make('payout_status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])->label('Payout Status'),
                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    })
                    ->label('Created Between'),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => \App\Filament\Resources\Transfers\TransferResource::getUrl('view', ['record' => $record])),
                EditAction::make(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url('/admin/exports/transfers.csv', true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
