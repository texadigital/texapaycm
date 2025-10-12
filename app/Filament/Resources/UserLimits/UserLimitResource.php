<?php

namespace App\Filament\Resources\UserLimits;

use App\Filament\Resources\UserLimits\Pages\ListUserLimits;
use App\Filament\Resources\UserLimits\Pages\EditUserLimit;
use App\Filament\Resources\UserLimits\Schemas\UserLimitForm;
use App\Filament\Resources\UserLimits\Tables\UserLimitsTable;
use App\Models\UserLimit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserLimitResource extends Resource
{
    protected static ?string $model = UserLimit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Limits & Risk';
    protected static ?int $navigationSort = 510;

    public static function form(Schema $schema): Schema
    {
        return UserLimitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserLimitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserLimits::route('/'),
            'edit' => EditUserLimit::route('/{record}/edit'),
        ];
    }
}
