<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AmlStrResource\Pages;
use App\Models\AmlStr;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class AmlStrResource extends Resource
{
    protected static ?string $model = AmlStr::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';
    protected static ?string $navigationLabel = 'STRs';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('id')->disabled()->label('ID'),
            Forms\Components\TextInput::make('reason')->disabled(),
            Forms\Components\Select::make('status')->options([
                'draft' => 'Draft',
                'submitted' => 'Submitted',
                'rejected' => 'Rejected',
            ])->disabled(),
            Forms\Components\TextInput::make('user_id')->label('User')->disabled(),
            Forms\Components\TextInput::make('transfer_id')->label('Transfer')->disabled(),
            Forms\Components\Textarea::make('payload')->label('Payload (JSON)')
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state) : (string) $state)
                ->disabled()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('User')->sortable(),
                Tables\Columns\TextColumn::make('transfer_id')->label('Transfer')->sortable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'draft',
                    'success' => 'submitted',
                    'danger' => 'rejected',
                ])->sortable(),
                Tables\Columns\TextColumn::make('reason')->wrap(),
                Tables\Columns\TextColumn::make('submitted_at')->dateTime()->since(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->filters([])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('submit')
                    ->label('Submit STR')
                    ->visible(fn () => auth()->user()?->is_admin)
                    ->requiresConfirmation()
                    ->color('success')
                    ->action(function (AmlStr $record) {
                        $record->update(['status' => 'submitted', 'submitted_at' => now()]);
                        try {
                            \App\Models\AmlAuditLog::create([
                                'actor_type' => 'admin',
                                'actor_id' => auth()->id(),
                                'action' => 'str.submitted',
                                'subject_type' => 'aml_str',
                                'subject_id' => $record->id,
                                'payload' => [
                                    'user_id' => $record->user_id,
                                    'transfer_id' => $record->transfer_id,
                                ],
                            ]);
                        } catch (\Throwable $e) { /* ignore */ }
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAmlStrs::route('/'),
            'view' => Pages\ViewAmlStr::route('/{record}'),
        ];
    }
}
