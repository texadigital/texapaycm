<?php

namespace App\Filament\Resources\Settings\FxSpreadResource\Pages;

use App\Filament\Resources\Settings\FxSpreadResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateFxSpread extends CreateRecord
{
    protected static string $resource = FxSpreadResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Settings\FxSpread $record */
        $record = $this->record;
        AdminActivity::log('fx_spread.created', $record, [], $record->attributesToArray());
    }
}
