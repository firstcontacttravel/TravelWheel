<?php

namespace App\Services;

use App\Mail\SupportExtraLuggageNotificationMail;
use App\Mail\SupportExtraLuggageSuccessMail;
use App\Mail\SupportFlightAssistNotificationMail;
use App\Mail\SupportFlightAssistSuccessMail;
use App\Mail\SupportVisaConfirmationNotificationMail;
use App\Mail\SupportVisaConfirmationSuccessMail;
use App\Mail\SupportYellowCardNotificationMail;
use App\Mail\SupportYellowCardSuccessMail;
use App\Models\SupportExtraLuggage;
use App\Models\SupportFlightAssist;
use App\Models\SupportProductPrice;
use App\Models\SupportVisaConfirmation;
use App\Models\SupportYellowCard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Creating and completing Travel Assist requests, whichever way they arrive:
 * the public forms or staff booking from the admin. The price is always worked
 * out here, never taken from the request.
 */
class SupportBookingService
{
    public const FLIGHT_ASSIST_DEFAULT_PRICE = 25000;

    public const YELLOW_CARD_SERVICES = [
        'standard' => ['label' => 'Standard (3 Days)', 'price' => 30000],
        'fasttrack' => ['label' => 'Fast Track (24 Hours)', 'price' => 50000],
    ];

    public const AIRLINES = [
        'local' => [
            'Air Peace', 'Dana Air', 'Ibom Air', 'Arik Air', 'Max Air', 'Azman Air', 'Aero Contractors',
            'Overland Airways', 'Med-View Airlines', 'Dornier Aviation Nigeria', 'Green Africa Airways',
            'ValueJet Nigeria', 'Kabo Air', 'First Nation Airways', 'Hak Air', 'Associated Airlines',
            'Rano Air', 'Umza Air', 'XEjet', 'United Nigeria',
        ],
        'international' => [
            'Qatar Airways', 'Emirates', 'British Airways', 'Ethiopian Airlines', 'Lufthansa', 'KLM',
            'Turkish Airlines', 'Air France', 'EgyptAir', 'South African Airways', 'Delta Airlines',
            'Virgin Atlantic', 'RwandAir', 'Royal Air Maroc', 'ASKY Airlines', 'FGB', 'Kenya Airways',
            'Air Maroc', 'Airpeace', 'Taag Angola', 'Uganda Airlines', 'United Airlines',
        ],
    ];

    public function flightAssistPrice(): float
    {
        return SupportProductPrice::amountFor('flight_assist', self::FLIGHT_ASSIST_DEFAULT_PRICE);
    }

    /**
     * Flight Assist isn't paid for here: the fee is billed together with the
     * client's main flight booking fee, so the client and support team are told
     * as soon as the request is made.
     */
    public function createFlightAssist(array $data): SupportFlightAssist
    {
        $record = SupportFlightAssist::create(array_merge($data, [
            'amount' => $this->flightAssistPrice(),
            'payment_option' => 'not_required',
            'payment_reference' => 'SPA-'.strtoupper(bin2hex(random_bytes(6))),
            'payment_status' => 'billed_with_main_fee',
        ]));

        $this->sendConfirmations($record);

        return $record;
    }

    public function createYellowCard(array $data, string $dataPagePath, string $paymentOption): SupportYellowCard
    {
        return SupportYellowCard::create([
            'service_type' => $data['service_type'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'data_page' => $dataPagePath,
            'home_address' => $data['home_address'],
            'phone_number' => $data['phone_number'],
            'delivery_address' => $data['delivery_address'],
            'payment_option' => $paymentOption,
            'payment_reference' => 'SYC-'.strtoupper(bin2hex(random_bytes(6))),
            'payment_status' => 'pending',
            'amount' => self::YELLOW_CARD_SERVICES[$data['service_type']]['price'],
        ]);
    }

    /** Marks a request paid and sends its confirmations, once however many times it's called. */
    public function completePayment(Model $record, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($record, $paymentOption): bool {
            $locked = $record::query()->lockForUpdate()->findOrFail($record->getKey());

            if ($locked->payment_status === 'paid') {
                return false;
            }

            $locked->update(array_filter(['payment_status' => 'paid', 'payment_option' => $paymentOption]));

            return true;
        });

        $record->refresh();

        if ($justPaid) {
            $this->sendConfirmations($record);
        }
    }

    private function sendConfirmations(Model $record): void
    {
        $supportMail = 'damilola@travelwheel.ng';

        try {
            match (true) {
                $record instanceof SupportFlightAssist => [
                    Mail::to($record->email)->send(new SupportFlightAssistSuccessMail($record)),
                    Mail::to($supportMail)->send(new SupportFlightAssistNotificationMail($record)),
                ],
                $record instanceof SupportExtraLuggage => [
                    Mail::to($record->email)->send(new SupportExtraLuggageSuccessMail($record)),
                    Mail::to($supportMail)->send(new SupportExtraLuggageNotificationMail($record, [
                        storage_path('app/public/'.$record->data_page),
                        storage_path('app/public/'.$record->ticket),
                    ])),
                ],
                $record instanceof SupportVisaConfirmation => [
                    Mail::to($record->email)->send(new SupportVisaConfirmationSuccessMail($record)),
                    Mail::to($supportMail)->send(new SupportVisaConfirmationNotificationMail($record, [
                        storage_path('app/public/'.$record->visa_file),
                    ])),
                ],
                $record instanceof SupportYellowCard => [
                    Mail::to($record->email)->send(new SupportYellowCardSuccessMail($record)),
                    Mail::to($supportMail)->send(new SupportYellowCardNotificationMail($record, [
                        storage_path('app/public/'.$record->data_page),
                    ])),
                ],
            };
        } catch (\Exception $e) {
            Log::error('Support mail error for '.$record->getMorphClass(), [
                'reference' => $record->payment_reference ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
