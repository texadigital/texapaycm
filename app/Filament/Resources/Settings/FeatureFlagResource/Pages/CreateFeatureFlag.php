<?php

namespace App\Filament\Resources\Settings\FeatureFlagResource\Pages;

use App\Filament\Resources\Settings\FeatureFlagResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateFeatureFlag extends CreateRecord
{
    protected static string $resource = FeatureFlagResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Settings\FeatureFlag $record */
        $record = $this->record;
        AdminActivity::log('feature_flag.created', $record, [], $record->attributesToArray());
    }
}
