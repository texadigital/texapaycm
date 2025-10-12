<?php

namespace App\Filament\Resources\Settings\FeatureFlagResource\Pages;

use App\Filament\Resources\Settings\FeatureFlagResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditFeatureFlag extends EditRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Settings\FeatureFlag $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('feature_flag.updated', $record, $before, $after);
    }
}
