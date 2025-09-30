<?php

namespace App\Filament\Resources\AdminSettings\Pages;

use App\Filament\Resources\AdminSettings\AdminSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminSettings extends ListRecords
{
    protected static string $resource = AdminSettingResource::class;
}
