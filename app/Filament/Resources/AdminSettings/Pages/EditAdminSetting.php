<?php

namespace App\Filament\Resources\AdminSettings\Pages;

use App\Filament\Resources\AdminSettings\AdminSettingResource;
use Filament\Resources\Pages\EditRecord;

class EditAdminSetting extends EditRecord
{
    protected static string $resource = AdminSettingResource::class;
}
