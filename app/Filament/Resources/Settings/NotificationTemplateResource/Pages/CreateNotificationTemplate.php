<?php

namespace App\Filament\Resources\Settings\NotificationTemplateResource\Pages;

use App\Filament\Resources\Settings\NotificationTemplateResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateNotificationTemplate extends CreateRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\Settings\NotificationTemplate $record */
        $record = $this->record;
        AdminActivity::log('notification_template.created', $record, [], $record->attributesToArray());
    }
}
