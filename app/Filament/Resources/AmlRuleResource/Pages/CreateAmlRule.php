<?php

namespace App\Filament\Resources\AmlRuleResource\Pages;

use App\Filament\Resources\AmlRuleResource;
use Filament\Resources\Pages\CreateRecord;
use App\Support\AdminActivity;

class CreateAmlRule extends CreateRecord
{
    protected static string $resource = AmlRuleResource::class;

    protected function afterCreate(): void
    {
        /** @var \App\Models\AmlRule $record */
        $record = $this->record;
        $after = $record->attributesToArray();
        AdminActivity::log('aml_rule.created', $record, [], $after);
    }
}
