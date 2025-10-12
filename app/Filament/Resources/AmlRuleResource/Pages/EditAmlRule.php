<?php

namespace App\Filament\Resources\AmlRuleResource\Pages;

use App\Filament\Resources\AmlRuleResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditAmlRule extends EditRecord
{
    protected static string $resource = AmlRuleResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\AmlRule $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('aml_rule.updated', $record, $before, $after);
    }
}
