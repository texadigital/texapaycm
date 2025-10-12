<?php

namespace App\Filament\Resources\Settings\FeeResource\Pages;

use App\Filament\Resources\Settings\FeeResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditFee extends EditRecord
{
    protected static string $resource = FeeResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Settings\Fee $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('fee.updated', $record, $before, $after);
    }
}
