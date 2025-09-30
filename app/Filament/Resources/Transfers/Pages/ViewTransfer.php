<?php

namespace App\Filament\Resources\Transfers\Pages;

use App\Filament\Resources\Transfers\TransferResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewTransfer extends ViewRecord
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receipt')
                ->label('View receipt')
                ->icon('heroicon-o-document-text')
                ->url(fn () => route('admin.transfer.receipt', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
