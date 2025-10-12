<?php

namespace App\Filament\Pages;

use App\Models\AdminSetting;
use App\Support\AdminActivity;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class KycSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Limits & Risk';
    protected static ?int $navigationSort = 506;

    protected string $view = 'filament.pages.kyc-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'kyc_enabled' => (bool) AdminSetting::getValue('kyc_enabled', false),
            'level0_per_tx' => (int) AdminSetting::getValue('kyc.level0.per_tx_cap_xaf', 50000),
            'level0_daily' => (int) AdminSetting::getValue('kyc.level0.daily_cap_xaf', 150000),
            'level0_monthly' => (int) AdminSetting::getValue('kyc.level0.monthly_cap_xaf', 1000000),
            'level1_per_tx' => (int) AdminSetting::getValue('kyc.level1.per_tx_cap_xaf', 2000000),
            'level1_daily' => (int) AdminSetting::getValue('kyc.level1.daily_cap_xaf', 5000000),
            'level1_monthly' => (int) AdminSetting::getValue('kyc.level1.monthly_cap_xaf', 50000000),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('kyc_enabled')->label('Enable KYC (Smile ID)')->inline(false),
                TextInput::make('level0_per_tx')->label('Level 0 Per-Transaction (XAF)')->numeric()->required()->minValue(0),
                TextInput::make('level0_daily')->label('Level 0 Daily (XAF)')->numeric()->required()->minValue(0),
                TextInput::make('level0_monthly')->label('Level 0 Monthly (XAF)')->numeric()->required()->minValue(0),
                TextInput::make('level1_per_tx')->label('Level 1 Per-Transaction (XAF)')->numeric()->required()->minValue(0),
                TextInput::make('level1_daily')->label('Level 1 Daily (XAF)')->numeric()->required()->minValue(0),
                TextInput::make('level1_monthly')->label('Level 1 Monthly (XAF)')->numeric()->required()->minValue(0),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        AdminSetting::setValue('kyc_enabled', (bool) $data['kyc_enabled'], 'boolean', 'Enable Smile ID KYC gating', 'kyc');
        AdminSetting::setValue('kyc.level0.per_tx_cap_xaf', (int) $data['level0_per_tx'], 'integer', 'Level 0 per-transaction cap (XAF)', 'kyc');
        AdminSetting::setValue('kyc.level0.daily_cap_xaf', (int) $data['level0_daily'], 'integer', 'Level 0 daily cap (XAF)', 'kyc');
        AdminSetting::setValue('kyc.level0.monthly_cap_xaf', (int) $data['level0_monthly'], 'integer', 'Level 0 monthly cap (XAF)', 'kyc');
        AdminSetting::setValue('kyc.level1.per_tx_cap_xaf', (int) $data['level1_per_tx'], 'integer', 'Level 1 per-transaction cap (XAF)', 'kyc');
        AdminSetting::setValue('kyc.level1.daily_cap_xaf', (int) $data['level1_daily'], 'integer', 'Level 1 daily cap (XAF)', 'kyc');
        AdminSetting::setValue('kyc.level1.monthly_cap_xaf', (int) $data['level1_monthly'], 'integer', 'Level 1 monthly cap (XAF)', 'kyc');

        // Audit log for KYC settings update
        try {
            AdminActivity::log('kyc.settings.updated', null, [], [], [
                'data' => $data,
            ]);
        } catch (\Throwable $e) { /* non-blocking */ }

        Notification::make()->title('KYC settings saved')->success()->send();
    }
}

