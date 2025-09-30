<?php

namespace App\Filament\Resources\UserLimits\Pages;

use App\Filament\Resources\UserLimits\UserLimitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUserLimit extends EditRecord
{
    protected static string $resource = UserLimitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
