<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycProfileResource\Pages;
use App\Models\KycProfile;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class KycProfileResource extends Resource
{
    protected static ?string $model = KycProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Compliance';
    protected static ?string $navigationLabel = 'KYC Profiles';

    public static function form(Schema $schema): Schema
    {
        // Read-only initial scaffold; policies will restrict edits
        return $schema->schema([
            Forms\Components\TextInput::make('id')->disabled(),
            Forms\Components\TextInput::make('user_id')->label('User')->disabled(),
            Forms\Components\TextInput::make('full_name')->disabled(),
            Forms\Components\TextInput::make('phone')->disabled(),
            Forms\Components\TextInput::make('id_type')->disabled(),
            Forms\Components\TextInput::make('id_number')->disabled(),
            Forms\Components\TextInput::make('id_image_path')->disabled(),
            Forms\Components\DatePicker::make('date_of_birth')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\DateTimePicker::make('created_at')->disabled(),
            Forms\Components\DateTimePicker::make('updated_at')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('User')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'verified',
                    'warning' => 'pending',
                    'danger' => 'rejected',
                ])->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'verified' => 'Verified',
                    'pending' => 'Pending',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Edit action will be guarded by policy; keep available for SuperAdmin
                Tables\Actions\EditAction::make()->visible(fn () => auth()->user()?->is_admin),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()?->is_admin),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKycProfiles::route('/'),
            'view' => Pages\ViewKycProfile::route('/{record}'),
            'edit' => Pages\EditKycProfile::route('/{record}/edit'),
        ];
    }
}
