<?php

namespace App\Filament\Resources\ProtectedTransactionResource\Pages;

use App\Filament\Resources\ProtectedTransactionResource;
use App\Models\ProtectedTransaction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewProtectedTransaction extends ViewRecord
{
    protected static string $resource = ProtectedTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('release')
                ->visible(fn(ProtectedTransaction $record) => in_array($record->escrow_state, [ProtectedTransaction::STATE_AWAITING, ProtectedTransaction::STATE_DISPUTED]))
                ->requiresConfirmation()
                ->action(fn(ProtectedTransaction $record) => redirect()->route('api.admin.protected.resolve', ['id' => $record->id, 'action' => 'release']))
                ->color('success')
                ->label('Release'),
            Actions\Action::make('refund')
                ->visible(fn(ProtectedTransaction $record) => $record->escrow_state === ProtectedTransaction::STATE_DISPUTED)
                ->requiresConfirmation()
                ->form([
                    Actions\Components\TextInput::make('partialAmountNgnMinor')->numeric()->label('Partial Amount (kobo)')->default(null),
                    Actions\Components\TextInput::make('note')->label('Note')->default(''),
                ])
                ->action(function (array $data, ProtectedTransaction $record) {
                    $params = ['id' => $record->id, 'action' => ($data['partialAmountNgnMinor'] ?? null) ? 'partial' : 'refund'];
                    if (!empty($data['partialAmountNgnMinor'])) { $params['partialAmountNgnMinor'] = (int) $data['partialAmountNgnMinor']; }
                    if (!empty($data['note'])) { $params['note'] = $data['note']; }
                    return redirect()->route('api.admin.protected.resolve', $params);
                })
                ->color('danger')
                ->label('Refund/Partial'),
        ];
    }
}
