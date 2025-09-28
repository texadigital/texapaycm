<?php

namespace App\Filament\Resources\UserLimits\Pages;

use App\Filament\Resources\UserLimits\UserLimitResource;
use Filament\Resources\Pages\ListRecords;

class ListUserLimits extends ListRecords
{
    protected static string $resource = UserLimitResource::class;
}
