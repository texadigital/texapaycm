<?php

namespace App\Filament\Resources\Settings\FeeResource\Pages;

use App\Filament\Resources\Settings\FeeResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateFee extends CreateRecord
{
    protected static string $resource = FeeResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Settings\Fee $record */
        $record = $this->record;
        AdminActivity::log('fee.created', $record, [], $record->attributesToArray());
    }
}
