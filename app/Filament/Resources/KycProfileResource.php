<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KycProfileResource\Pages;
use App\Models\KycProfile;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Actions;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class KycProfileResource extends Resource
{
    protected static ?string $model = KycProfile::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-identification';
    protected static string|\UnitEnum|null $navigationGroup = 'Customers';
    protected static ?string $navigationLabel = 'KYC Profiles';

    public static function form(Schema $schema): Schema
    {
        // Read-only initial scaffold; policies will restrict edits
        return $schema->schema([
            Forms\Components\TextInput::make('id')->disabled(),
            Forms\Components\TextInput::make('user.name')->label('User')->disabled(),
            Forms\Components\TextInput::make('user.email')->label('Email')->disabled(),
            Forms\Components\TextInput::make('full_name')->label('Full name')->disabled(),
            Forms\Components\TextInput::make('phone')->label('Phone')->disabled(),
            Forms\Components\TextInput::make('id_type')->label('ID Type')->disabled(),
            Forms\Components\TextInput::make('id_number')->label('ID Number')->disabled(),
            Forms\Components\TextInput::make('id_image_path')->label('ID Image')->disabled(),
            Forms\Components\DatePicker::make('date_of_birth')->label('DOB')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\DateTimePicker::make('created_at')->label('Created')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('full_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'verified',
                    'warning' => 'pending',
                    'danger' => 'rejected',
                ])->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('Y-m-d H:i')->since()->label('Created')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'verified' => 'Verified',
                    'pending' => 'Pending',
                    'rejected' => 'Rejected',
                ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                // Edit action will be guarded by policy; keep available for SuperAdmin
                Actions\EditAction::make()->visible(fn () => auth()->user()?->is_admin),
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
            'index' => Pages\ListKycProfiles::route('/'),
            'view' => Pages\ViewKycProfile::route('/{record}'),
            'edit' => Pages\EditKycProfile::route('/{record}/edit'),
        ];
    }
}
