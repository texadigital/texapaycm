<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EddCaseResource\Pages;
use App\Models\EddCase;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EddCaseResource extends Resource
{
    protected static ?string $model = EddCase::class;

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string|\UnitEnum|null $navigationGroup = 'Compliance';
    protected static ?string $navigationLabel = 'EDD Cases';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('case_ref')->disabled(),
            Forms\Components\TextInput::make('risk_reason')->required(),
            Forms\Components\Select::make('status')->options([
                'open' => 'Open',
                'pending_docs' => 'Pending Docs',
                'review' => 'In Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'closed' => 'Closed',
            ])->required(),
            Forms\Components\Select::make('owner_id')
                ->relationship('owner', 'name')
                ->searchable()
                ->preload()
                ->nullable(),
            Forms\Components\DateTimePicker::make('sla_due_at')->label('SLA Due'),
            Forms\Components\Textarea::make('metadata')->label('Metadata (JSON)')
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
                Tables\Columns\TextColumn::make('case_ref')->copyable()->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('User')->searchable(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'danger' => 'open',
                    'warning' => 'pending_docs',
                    'info' => 'review',
                    'success' => 'approved',
                    'gray' => 'closed',
                ])->sortable(),
                Tables\Columns\TextColumn::make('risk_reason')->wrap()->searchable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Owner')->toggleable(),
                Tables\Columns\TextColumn::make('sla_due_at')->dateTime()->since()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'open' => 'Open',
                    'pending_docs' => 'Pending Docs',
                    'review' => 'In Review',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'closed' => 'Closed',
                ]),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->visible(fn () => auth()->user()?->is_admin)
                    ->requiresConfirmation()
                    ->color('success')
                    ->action(function (EddCase $record) {
                        $record->update(['status' => 'approved']);
                        try {
                            \App\Models\AmlAuditLog::create([
                                'actor_type' => 'admin',
                                'actor_id' => auth()->id(),
                                'action' => 'edd.approved',
                                'subject_type' => 'edd_case',
                                'subject_id' => $record->id,
                                'payload' => [
                                    'user_id' => $record->user_id,
                                ],
                            ]);
                        } catch (\Throwable $e) { /* ignore */ }
                    }),
                Actions\Action::make('reject')
                    ->label('Reject')
                    ->visible(fn () => auth()->user()?->is_admin)
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (EddCase $record) {
                        $record->update(['status' => 'rejected']);
                        try {
                            \App\Models\AmlAuditLog::create([
                                'actor_type' => 'admin',
                                'actor_id' => auth()->id(),
                                'action' => 'edd.rejected',
                                'subject_type' => 'edd_case',
                                'subject_id' => $record->id,
                                'payload' => [
                                    'user_id' => $record->user_id,
                                ],
                            ]);
                        } catch (\Throwable $e) { /* ignore */ }
                    }),
                Actions\Action::make('close')
                    ->label('Close')
                    ->visible(fn () => auth()->user()?->is_admin)
                    ->requiresConfirmation()
                    ->color('gray')
                    ->action(function (EddCase $record) {
                        $record->update(['status' => 'closed']);
                        try {
                            \App\Models\AmlAuditLog::create([
                                'actor_type' => 'admin',
                                'actor_id' => auth()->id(),
                                'action' => 'edd.closed',
                                'subject_type' => 'edd_case',
                                'subject_id' => $record->id,
                                'payload' => [
                                    'user_id' => $record->user_id,
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
            'index' => Pages\ListEddCases::route('/'),
            'view' => Pages\ViewEddCase::route('/{record}'),
            'edit' => Pages\EditEddCase::route('/{record}/edit'),
        ];
    }
}
