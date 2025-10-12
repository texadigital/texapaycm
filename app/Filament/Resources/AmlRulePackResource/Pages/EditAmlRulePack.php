<?php

namespace App\Filament\Resources\AmlRulePackResource\Pages;

use App\Filament\Resources\AmlRulePackResource;
use Filament\Resources\Pages\EditRecord;
use App\Support\AdminActivity;

class EditAmlRulePack extends EditRecord
{
    protected static string $resource = AmlRulePackResource::class;

    protected function afterSave(): void
    {
        /** @var \App\Models\AmlRulePack $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        $before = $record->getOriginal();
        AdminActivity::log('aml_rule_pack.updated', $record, $before, $after);
    }
}
