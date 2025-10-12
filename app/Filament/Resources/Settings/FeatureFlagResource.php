<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\FeatureFlagResource\Pages;
use App\Models\Settings\FeatureFlag;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;

class FeatureFlagResource extends Resource
{
    protected static ?string $model = FeatureFlag::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';
    protected static string|\UnitEnum|null $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 810;
    protected static ?string $navigationLabel = 'Feature Flags';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('key')
                ->required()
                ->maxLength(128)
                ->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('enabled')
                ->inline(false)
                ->default(false),
            Forms\Components\TextInput::make('rollout_percent')
                ->numeric()
                ->minValue(0)
                ->maxValue(100)
                ->default(100)
                ->label('Rollout %'),
            Forms\Components\Select::make('category')
                ->options([
                    'pricing' => 'Pricing',
                    'mobile' => 'Mobile',
                    'providers' => 'Providers',
                    'notifications' => 'Notifications',
                    'kyc' => 'KYC',
                    'risk' => 'Risk',
                    'general' => 'General',
                ])->native(false),
            Forms\Components\Textarea::make('description')->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('key')->label('Flag Key')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->label('Enabled')->sortable(),
                Tables\Columns\TextColumn::make('rollout_percent')->label('Rollout %')->alignRight()->sortable(),
                Tables\Columns\TextColumn::make('category')->label('Category')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i')->since()->label('Updated')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')->boolean(),
                Tables\Filters\SelectFilter::make('category')->options([
                    'pricing' => 'Pricing',
                    'mobile' => 'Mobile',
                    'providers' => 'Providers',
                    'notifications' => 'Notifications',
                    'kyc' => 'KYC',
                    'risk' => 'Risk',
                    'general' => 'General',
                ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make()->visible(fn () => auth()->user()?->is_admin),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()?->is_admin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureFlags::route('/'),
            'create' => Pages\CreateFeatureFlag::route('/create'),
            'edit' => Pages\EditFeatureFlag::route('/{record}/edit'),
        ];
    }
}
