<?php

namespace App\Filament\Resources\AmlAlertResource\Pages;

use App\Filament\Resources\AmlAlertResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewAmlAlert extends ViewRecord
{
    protected static string $resource = AmlAlertResource::class;

    protected function getInfolistSchema(): array
    {
        return [
            Section::make('Alert')
                ->schema([
                    TextEntry::make('id'),
                    TextEntry::make('user.name')->label('User'),
                    TextEntry::make('transfer_id')->label('Transfer'),
                    TextEntry::make('rule_key'),
                    TextEntry::make('severity'),
                    TextEntry::make('status'),
                    TextEntry::make('created_at')->since(),
                ])->columns(2),
            Section::make('Context')
                ->schema([
                    KeyValueEntry::make('context')
                        ->keyLabel('Field')
                        ->valueLabel('Value'),
                ])->columnSpanFull(),
        ];
    }
}
