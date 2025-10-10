<?php

namespace App\Filament\Pages;

use App\Models\AdminSetting;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class AmlSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected string $view = 'filament.pages.aml-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'block_on_open' => (bool) AdminSetting::getValue('aml.edd.block_on_open', false),
            'block_reasons' => (string) AdminSetting::getValue('aml.edd.block_reasons', 'sanctions,pep'),
            'per_tx_trigger_xaf' => (int) AdminSetting::getValue('aml.edd.per_tx_trigger_xaf', 500000),
            // Extended thresholds
            'per_tx_trigger_ngn' => (int) AdminSetting::getValue('aml.edd.per_tx_trigger_ngn', 5000000),
            'fx_trigger_usd' => (int) AdminSetting::getValue('aml.edd.fx_trigger_usd', 5000),
            'daily_txn_count_threshold' => (int) AdminSetting::getValue('aml.monitor.daily_txn_count_threshold', 10),
            'fatf_block_countries' => (string) AdminSetting::getValue('aml.cft.fatf_block_countries', ''),
            'terror_keywords' => (string) AdminSetting::getValue('aml.cft.terror_keywords', 'donation,charity,relief'),
            'six_month_reverify_enabled' => (bool) AdminSetting::getValue('aml.edd.six_month_reverify_enabled', true),
            'str_auto_create_enabled' => (bool) AdminSetting::getValue('aml.str.auto_create_enabled', true),
            // test toggles
            'force_review' => (bool) AdminSetting::getValue('aml.screening.force_review', false),
            'force_sanctions' => (bool) AdminSetting::getValue('aml.screening.force_sanctions', false),
            'force_pep' => (bool) AdminSetting::getValue('aml.screening.force_pep', false),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('block_on_open')->label('Block transactions when EDD case opens')->inline(false),
                TextInput::make('block_reasons')->label('Blocking reasons (CSV)')
                    ->helperText('Comma-separated reasons that auto-block: sanctions, pep, adverse, review')
                    ->default('sanctions,pep'),
                TextInput::make('per_tx_trigger_xaf')->label('Per-transaction trigger (XAF)')
                    ->numeric()->required()->minValue(0),
                TextInput::make('per_tx_trigger_ngn')->label('Per-transaction trigger (NGN)')
                    ->numeric()->required()->minValue(0),
                TextInput::make('fx_trigger_usd')->label('FX conversion trigger (USD equiv)')
                    ->numeric()->required()->minValue(0),
                TextInput::make('daily_txn_count_threshold')->label('Daily transactions threshold (24h)')
                    ->numeric()->required()->minValue(1),
                TextInput::make('fatf_block_countries')->label('FATF grey/black list (CSV ISO codes)')
                    ->helperText('e.g., IR, KP, MM'),
                TextInput::make('terror_keywords')->label('CFT Keywords (CSV)')
                    ->helperText('e.g., donation,charity,relief'),
                Toggle::make('six_month_reverify_enabled')->label('Enable 6-month re-verification for high-risk/PEP'),
                Toggle::make('str_auto_create_enabled')->label('Enable auto-create STR on confirmed suspicious cases'),
                Section::make('Testing toggles')->schema([
                    Toggle::make('force_review')->label('Force review result'),
                    Toggle::make('force_sanctions')->label('Force sanctions hit'),
                    Toggle::make('force_pep')->label('Force PEP match'),
                ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        AdminSetting::setValue('aml.edd.block_on_open', (bool) $data['block_on_open'], 'boolean', 'Block on EDD case open', 'aml');
        AdminSetting::setValue('aml.edd.block_reasons', (string) $data['block_reasons'], 'string', 'Reasons that block (csv)', 'aml');
        AdminSetting::setValue('aml.edd.per_tx_trigger_xaf', (int) $data['per_tx_trigger_xaf'], 'integer', 'Per-transaction trigger (XAF)', 'aml');
        AdminSetting::setValue('aml.edd.per_tx_trigger_ngn', (int) $data['per_tx_trigger_ngn'], 'integer', 'Per-transaction trigger (NGN)', 'aml');
        AdminSetting::setValue('aml.edd.fx_trigger_usd', (int) $data['fx_trigger_usd'], 'integer', 'FX conversion trigger (USD)', 'aml');
        AdminSetting::setValue('aml.monitor.daily_txn_count_threshold', (int) $data['daily_txn_count_threshold'], 'integer', 'Daily txn count threshold', 'aml');
        AdminSetting::setValue('aml.cft.fatf_block_countries', (string) $data['fatf_block_countries'], 'string', 'FATF block list (CSV)', 'aml');
        AdminSetting::setValue('aml.cft.terror_keywords', (string) $data['terror_keywords'], 'string', 'CFT keywords (CSV)', 'aml');
        AdminSetting::setValue('aml.edd.six_month_reverify_enabled', (bool) $data['six_month_reverify_enabled'], 'boolean', 'Enable 6-month reverify', 'aml');
        AdminSetting::setValue('aml.str.auto_create_enabled', (bool) $data['str_auto_create_enabled'], 'boolean', 'Enable auto STR creation', 'aml');
        // testing toggles
        AdminSetting::setValue('aml.screening.force_review', (bool) $data['force_review'], 'boolean', 'Force review result', 'aml');
        AdminSetting::setValue('aml.screening.force_sanctions', (bool) $data['force_sanctions'], 'boolean', 'Force sanctions hit', 'aml');
        AdminSetting::setValue('aml.screening.force_pep', (bool) $data['force_pep'], 'boolean', 'Force PEP match', 'aml');

        Notification::make()->title('AML settings saved')->success()->send();
    }
}

