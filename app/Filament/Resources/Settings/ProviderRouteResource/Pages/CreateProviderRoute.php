<?php

namespace App\Filament\Resources\Settings\ProviderRouteResource\Pages;

use App\Filament\Resources\Settings\ProviderRouteResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateProviderRoute extends CreateRecord
{
    protected static string $resource = ProviderRouteResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Settings\ProviderRoute $record */
        $record = $this->record;
        AdminActivity::log('provider_route.created', $record, [], $record->attributesToArray());
    }
}
