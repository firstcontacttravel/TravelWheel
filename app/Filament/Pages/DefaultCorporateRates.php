<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Companies\Schemas\CorporateRatesForm;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Services\Corporate\CompanyInvoices;
use App\Services\Corporate\CompanyWalletTopUps;
use App\Services\Corporate\CorporateRates;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The service charge for any company without its own rate. Blank falls back to
 * the public rate. Only the CEO sets rates.
 */
class DefaultCorporateRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Corporate';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Default Rates';

    protected static ?string $title = 'Default corporate rates';

    protected static ?string $slug = 'default-corporate-rates';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->form->fill([
            'rates' => app(CorporateRates::class)->saved(null),
            'card_topup_fee_percent' => AppSetting::get(CompanyWalletTopUps::FEE_SETTING),
            'vat_percent' => AppSetting::get(CompanyInvoices::VAT_SETTING),
        ]);
    }

    public function getSubheading(): ?string
    {
        return 'Charged per passenger to any company without its own rate. Leave a rate blank to use the public rate.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Card top-ups and VAT')
                ->columns(2)
                ->schema([
                    TextInput::make('card_topup_fee_percent')
                        ->label('TravelWheel card fee')
                        ->helperText('SeerBit already adds its own fee at checkout, so 0% means the company pays only that. Anything above 0 is charged on top of it. Blank switches card top-ups off.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->suffix('%'),
                    TextInput::make('vat_percent')
                        ->label('VAT on invoices')
                        ->helperText('Shown as included in the service fee, on invoices issued from now on. Blank leaves VAT off. Check with your accountant.')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(50)
                        ->suffix('%'),
                ]),
            ...CorporateRatesForm::sections(null),
        ])->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Save rates')->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $rates = app(CorporateRates::class);
        $state = $this->form->getState();
        $before = $rates->saved(null);
        $feeBefore = AppSetting::get(CompanyWalletTopUps::FEE_SETTING);
        $above = $rates->save(null, $state['rates'] ?? []);

        $fee = filled($state['card_topup_fee_percent'] ?? null) ? (string) (float) $state['card_topup_fee_percent'] : null;
        AppSetting::set(CompanyWalletTopUps::FEE_SETTING, $fee);

        $vatBefore = AppSetting::get(CompanyInvoices::VAT_SETTING);
        $vat = filled($state['vat_percent'] ?? null) ? (string) (float) $state['vat_percent'] : null;
        AppSetting::set(CompanyInvoices::VAT_SETTING, $vat);

        ActivityLog::record('corporate.default_rates_updated', 'Updated default corporate rates', properties: [
            'before' => $before,
            'after' => $rates->saved(null),
            'card_fee_before' => $feeBefore,
            'card_fee_after' => $fee,
            'vat_before' => $vatBefore,
            'vat_after' => $vat,
        ]);

        CorporateRatesForm::notifySaved($above);
    }
}
