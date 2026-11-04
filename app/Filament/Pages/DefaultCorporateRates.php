<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Companies\Schemas\CorporateRatesForm;
use App\Models\ActivityLog;
use App\Models\AppSetting;
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
        ]);
    }

    public function getSubheading(): ?string
    {
        return 'Charged per passenger to any company without its own rate. Leave a rate blank to use the public rate.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Card top-up fee')
                ->description('Added on top when a company tops up its wallet by card. It never goes into the wallet. Leave it blank to keep card top-ups switched off.')
                ->schema([
                    TextInput::make('card_topup_fee_percent')
                        ->label('Fee')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(20)
                        ->suffix('%')
                        ->maxWidth('xs'),
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

        ActivityLog::record('corporate.default_rates_updated', 'Updated default corporate rates', properties: [
            'before' => $before,
            'after' => $rates->saved(null),
            'card_fee_before' => $feeBefore,
            'card_fee_after' => $fee,
        ]);

        CorporateRatesForm::notifySaved($above);
    }
}
