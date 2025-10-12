<?php

namespace App\Filament\Resources\AmlRulePackResource\Pages;

use App\Filament\Resources\AmlRulePackResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateAmlRulePack extends CreateRecord
{
    protected static string $resource = AmlRulePackResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\AmlRulePack $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        AdminActivity::log('aml_rule_pack.created', $record, [], $after);
    }
}
