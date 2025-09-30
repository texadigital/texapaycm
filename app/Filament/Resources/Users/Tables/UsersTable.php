<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use App\Models\User;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable()->label('ID'),
                TextColumn::make('name')->searchable(),
                TextColumn::make('phone')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('kyc_status')->badge(),
                TextColumn::make('kyc_level')->label('Level')->sortable(),
                TextColumn::make('kyc_verified_at')->since()->label('Verified At'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('promoteKyc')
                    ->label('Promote to Level 1')
                    ->color('success')
                    ->visible(fn (User $record) => (int) ($record->kyc_level ?? 0) === 0)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['kyc_level' => 1, 'kyc_status' => 'verified', 'kyc_verified_at' => now()]);
                    }),
                Action::make('demoteKyc')
                    ->label('Demote to Level 0')
                    ->color('danger')
                    ->visible(fn (User $record) => (int) ($record->kyc_level ?? 1) === 1)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['kyc_level' => 0]);
                    }),
            ]);
    }
}
