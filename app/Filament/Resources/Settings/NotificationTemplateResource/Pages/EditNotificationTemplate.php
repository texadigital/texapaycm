<?php

namespace App\Filament\Resources\Settings\NotificationTemplateResource\Pages;

use App\Filament\Resources\Settings\NotificationTemplateResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditNotificationTemplate extends EditRecord
{
    protected static string $resource = NotificationTemplateResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\Settings\NotificationTemplate $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('notification_template.updated', $record, $before, $after);
    }
}
