<?php

namespace App\Filament\Resources\Settings\FeatureFlagResource\Pages;

use App\Filament\Resources\Settings\FeatureFlagResource;
use Filament\Resources\Pages\ListRecords;

class ListFeatureFlags extends ListRecords
{
    protected static string $resource = FeatureFlagResource::class;
}
