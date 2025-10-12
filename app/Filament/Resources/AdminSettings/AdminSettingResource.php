<?php

namespace App\Filament\Resources\AdminSettings;

use App\Filament\Resources\AdminSettings\Pages\ListAdminSettings;
use App\Filament\Resources\AdminSettings\Pages\EditAdminSetting;
use App\Filament\Resources\AdminSettings\Schemas\AdminSettingForm;
use App\Filament\Resources\AdminSettings\Tables\AdminSettingsTable;
use App\Models\AdminSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AdminSettingResource extends Resource
{
    protected static ?string $model = AdminSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 820;

    public static function shouldRegisterNavigation(): bool
    {
        // Hide legacy Admin Settings from the sidebar to keep a single Settings entry.
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AdminSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminSettings::route('/'),
            'edit' => EditAdminSetting::route('/{record}/edit'),
        ];
    }
}
