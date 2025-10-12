<?php

namespace App\Filament\Resources\Settings\FxSpreadResource\Pages;

use App\Filament\Resources\Settings\FxSpreadResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditFxSpread extends EditRecord
{
    protected static string $resource = FxSpreadResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Settings\FxSpread $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('fx_spread.updated', $record, $before, $after);
    }
}
