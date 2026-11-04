<?php

namespace App\Filament\Resources\Companies\Schemas;

use App\Filament\Resources\FlightServiceCharges\FlightServiceChargeResource;
use App\Models\Company;
use App\Services\Corporate\CorporateRates;
use App\Support\FlightMarkup;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

/**
 * The route and cabin table for corporate rates, used for one company and for
 * the default. Each field shows the public rate beside it, and warns as soon as
 * a rate goes above it. A warning doesn't stop the save: some companies may pay
 * more for extra service.
 */
class CorporateRatesForm
{
    /** @return list<Section> */
    public static function sections(?Company $company): array
    {
        $fallback = $company ? app(CorporateRates::class)->saved(null) : [];
        $sections = [];

        foreach (FlightMarkup::publicCharges() as $category => $cabins) {
            $fields = [];

            foreach ($cabins as $cabin => $public) {
                $placeholder = $fallback[$category][$cabin] ?? $public;

                $fields[] = TextInput::make("rates.{$category}.{$cabin}")
                    ->label(FlightServiceChargeResource::cabinLabel($cabin))
                    ->numeric()
                    ->minValue(0)
                    ->prefix('₦')
                    ->placeholder(number_format($placeholder))
                    ->helperText('Public rate: ₦'.number_format($public))
                    ->live(onBlur: true)
                    ->hint(fn (mixed $state): ?string => is_numeric($state) && (float) $state > $public ? 'Above the public rate' : null)
                    ->hintColor('danger')
                    ->hintIcon(fn (mixed $state): ?string => is_numeric($state) && (float) $state > $public ? 'heroicon-o-exclamation-triangle' : null);
            }

            $sections[] = Section::make(FlightServiceChargeResource::routeLabel($category))->columns(4)->schema($fields);
        }

        return $sections;
    }

    /** Sends the right message after saving, warning about any rate above the public one. */
    public static function notifySaved(array $above): void
    {
        if ($above === []) {
            Notification::make()->success()->title('Rates saved')->send();

            return;
        }

        Notification::make()
            ->warning()
            ->title('Rates saved, but '.count($above).' '.(count($above) === 1 ? 'is' : 'are').' above the public rate')
            ->body('This company would pay more than the public site for these fares. Check that\'s what you intended.')
            ->persistent()
            ->send();
    }
}
