<?php

namespace App\Filament\Resources\AmlAlertResource\Pages;

use App\Filament\Resources\AmlAlertResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditAmlAlert extends EditRecord
{
    protected static string $resource = AmlAlertResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\AmlAlert $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('aml_alert.updated', $record, $before, $after);
    }
}
