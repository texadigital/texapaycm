<?php

namespace App\Filament\Resources\EddCaseResource\Pages;

use App\Filament\Resources\EddCaseResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditEddCase extends EditRecord
{
    protected static string $resource = EddCaseResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\EddCase $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('edd_case.updated', $record, $before, $after);
    }
}
