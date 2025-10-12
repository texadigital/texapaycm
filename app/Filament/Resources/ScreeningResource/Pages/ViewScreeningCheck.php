<?php

namespace App\Filament\Resources\ScreeningResource\Pages;

use App\Filament\Resources\ScreeningResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;

class ViewScreeningCheck extends ViewRecord
{
    protected static string $resource = ScreeningResource::class;

    protected function getInfolistSchema(): array
    {
        return [
            Section::make('Check')
                ->schema([
                    TextEntry::make('id'),
                    TextEntry::make('user.name')->label('User'),
                    TextEntry::make('type'),
                    TextEntry::make('provider'),
                    TextEntry::make('status'),
                    TextEntry::make('completed_at')->since(),
                    TextEntry::make('created_at')->since(),
                ])->columns(2),
            Section::make('Results')
                ->schema([
                    KeyValueEntry::make('results')
                        ->keyLabel('Field')
                        ->valueLabel('Value')
                        ->default(fn ($record) => $record->results()->latest('id')->first()?->raw ?? []),
                ])->columnSpanFull(),
        ];
    }
}
