<?php

namespace App\Filament\Resources\Settings\ProviderRouteResource\Pages;

use App\Filament\Resources\Settings\ProviderRouteResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditProviderRoute extends EditRecord
{
    protected static string $resource = ProviderRouteResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Settings\ProviderRoute $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('provider_route.updated', $record, $before, $after);
    }
}
