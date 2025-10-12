<?php

namespace App\Filament\Resources\KycProfileResource\Pages;

use App\Filament\Resources\KycProfileResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditKycProfile extends EditRecord
{
    protected static string $resource = KycProfileResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\KycProfile $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('kyc_profile.updated', $record, $before, $after);
    }
}
