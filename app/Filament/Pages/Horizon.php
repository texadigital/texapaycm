<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class Horizon extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 910;
    protected static ?string $navigationLabel = 'Horizon';

    protected string $view = 'filament.pages.horizon';
}
