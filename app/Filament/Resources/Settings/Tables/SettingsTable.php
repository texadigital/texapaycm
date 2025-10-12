<?php

namespace App\Filament\Resources\Settings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

class SettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                // Add SQL computed column for provider vendor so grouping/sorting works on the alias safely
                $caseSql = <<<'SQL'
CASE WHEN `group` = 'providers' THEN
    CASE
        WHEN `key` LIKE 'pawapay.%' THEN 'PawaPay'
        WHEN `key` LIKE 'safehaven.%' THEN 'SafeHaven'
        WHEN `key` LIKE 'oxr.%' THEN 'Open Exchange Rates'
        ELSE 'Other Provider'
    END
ELSE NULL END AS provider_vendor
SQL;
                $query->select('*')->addSelect(DB::raw($caseSql));
            })
            ->columns([
                TextColumn::make('label')
                    ->label('Label')
                    ->formatStateUsing(function ($state, $record) {
                        if (!empty($state)) return (string) $state;
                        $key = (string) ($record->key ?? '');
                        $key = str_replace(['.', '_'], ' ', $key);
                        return \Illuminate\Support\Str::title($key);
                    })
                    ->searchable()
                    ->sortable()
                    ->wrap(false),
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->wrap(false),
                BadgeColumn::make('provider_vendor')
                    ->label('Provider')
                    ->colors([
                        'primary' => 'PawaPay',
                        'success' => 'SafeHaven',
                        'info' => 'Open Exchange Rates',
                    ])
                    ->formatStateUsing(function ($state, $record) {
                        return $record->provider_vendor ?? null;
                    })
                    ->toggleable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->formatStateUsing(function ($state, $record) {
                        $type = (string) ($record->type ?? 'string');
                        $key = (string) ($record->key ?? '');
                        $value = $state;

                        $isSecret = (bool) preg_match('/(secret|key|token|password|api|webhook)/i', $key);
                        if ($isSecret) {
                            $str = is_scalar($value) ? (string) $value : json_encode($value);
                            if ($str === '' || $str === null) return '—';
                            $visible = substr((string) $str, -4);
                            $maskLen = max(strlen((string) $str) - 4, 0);
                            return str_repeat('•', $maskLen) . $visible;
                        }

                        if ($type === 'boolean') {
                            return $value ? 'true' : 'false';
                        }

                        if ($type === 'json') {
                            $json = is_string($value) ? $value : json_encode($value);
                            $pretty = json_encode(json_decode((string) $json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            return \Illuminate\Support\Str::limit($pretty ?: (string) $json, 120);
                        }

                        return is_scalar($value) ? (string) $value : json_encode($value);
                    })
                    ->url(function ($record) {
                        $key = (string) ($record->key ?? '');
                        $isSecret = (bool) preg_match('/(secret|key|token|password|api|webhook)/i', $key);
                        if ($isSecret) return null;
                        $raw = (string) ($record->value ?? '');
                        if (preg_match('/^https?:\/\//i', $raw)) {
                            return $raw;
                        }
                        return null;
                    })
                    ->openUrlInNewTab()
                    ->tooltip(function ($state, $record) {
                        $type = (string) ($record->type ?? 'string');
                        $key = (string) ($record->key ?? '');
                        $isSecret = (bool) preg_match('/(secret|key|token|password|api|webhook)/i', $key);
                        if ($isSecret) return null;
                        if ($type === 'json') {
                            $json = is_string($state) ? $state : json_encode($state);
                            $pretty = json_encode(json_decode((string) $json, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            return $pretty ?: $state;
                        }
                        return null;
                    })
                    ->wrap()
                    ->toggleable(),
                BadgeColumn::make('group')
                    ->colors([
                        'primary' => 'providers',
                        'success' => 'fees',
                        'warning' => 'fx',
                        'info' => 'limits',
                        'gray' => 'toggles',
                        'pink' => 'copy',
                        'secondary' => 'general',
                    ])
                    ->sortable(),
                TextColumn::make('type')
                    ->sortable(),
                IconColumn::make('enabled')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->groups([
                Group::make('provider_vendor')
                    ->label('Provider')
                    ->collapsible(),
                Group::make('group')
                    ->label('Group')
                    ->collapsible(),
            ])
            ->defaultGroup('group')
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'providers' => 'Providers',
                        'fees' => 'Fees',
                        'fx' => 'FX',
                        'limits' => 'Limits',
                        'toggles' => 'Toggles',
                        'copy' => 'Copy/Labels',
                        'general' => 'General',
                    ]),
                TernaryFilter::make('enabled')
                    ->placeholder('All')
                    ->trueLabel('Enabled')
                    ->falseLabel('Disabled'),
                SelectFilter::make('provider')
                    ->label('Provider')
                    ->options([
                        'pawapay' => 'PawaPay',
                        'safehaven' => 'SafeHaven',
                        'oxr' => 'Open Exchange Rates',
                    ])
                    ->query(function ($query, $value) {
                        if (!$value) return $query;
                        return $query->where('group', 'providers')
                            ->where('key', 'like', $value . '.%');
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
