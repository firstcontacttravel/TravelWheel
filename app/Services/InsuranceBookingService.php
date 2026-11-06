<?php

namespace App\Services;

use App\Mail\InsurancePurchaseNotificationMail;
use App\Models\InsurancePurchase;
use App\Models\InsuranceQuote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Travel insurance with Sanlam Allianz, whichever way it's bought: the public
 * pages or staff booking from the admin. The price is Sanlam's quote, kept when
 * the quote was made, plus VAT: never an amount the browser sent. The policy is
 * issued once the customer has paid.
 *
 * The booking forms ($form, $nextOfKin) are keyed as the website's forms are,
 * so a policy is issued the same way however it was bought.
 */
class InsuranceBookingService
{
    public const VAT_PERCENT = 7.5;

    public const INDIVIDUAL = 1;

    public const FAMILY = 2;

    /** The quote a booking form is for, by the quote request id it carries. */
    public function quote(?string $quoteRequestId): ?InsuranceQuote
    {
        if (blank($quoteRequestId)) {
            return null;
        }

        $quote = InsuranceQuote::where('quoteRequestId', $quoteRequestId)->latest('id')->first();

        return $quote && (float) $quote->amount > 0 ? $quote : null;
    }

    /** @return array{amount: float, vat: float, total: float} */
    public function price(InsuranceQuote $quote): array
    {
        $amount = round((float) $quote->amount, 2);
        $vat = round($amount * self::VAT_PERCENT / 100, 2);

        return ['amount' => $amount, 'vat' => $vat, 'total' => round($amount + $vat, 2)];
    }

    /**
     * Asks Sanlam for a quote and keeps it.
     *
     * @param  array{dob: string, email: string, phone_no: string, begin_date: string, end_date: string, country: string,
     *     purpose_of_travel: string, travel_plan: string, booking_type: int|string, nop?: int, noc?: int}  $trip  dates as Y-m-d
     *
     * @throws RuntimeException when Sanlam can't give one
     */
    public function requestQuote(array $trip): InsuranceQuote
    {
        $data = Http::withToken($this->token())->acceptJson()->post($this->baseUrl().'/Travel/api/Quote', [
            'DateOfBirth' => $this->sanlamDate($trip['dob']),
            'Email' => $trip['email'],
            'Telephone' => $trip['phone_no'],
            'CoverBegins' => $this->sanlamDate($trip['begin_date']),
            'CoverEnds' => $this->sanlamDate($trip['end_date']),
            'CountryId' => $trip['country'],
            'PurposeOfTravel' => $trip['purpose_of_travel'],
            'TravelPlanId' => $trip['travel_plan'] === 'Schengen' ? 1 : 2,
            'BookingTypeId' => $trip['booking_type'],
            'IsRoundTrip' => false,
            'NoOfPeople' => $trip['nop'] ?? 1,
            'NoOfChildren' => $trip['noc'] ?? 0,
            'IsMultiTrip' => false,
        ])->throw()->json();

        if (empty($data['QuoteRequestId']) || ! isset($data['Amount'])) {
            throw new RuntimeException('Sanlam returned no quote.');
        }

        return InsuranceQuote::create([
            'quoteRequestId' => $data['QuoteRequestId'],
            'productVariantId' => $data['ProductVariantId'] ?? null,
            'dob' => $data['DateOfBirth'] ?? null,
            'email' => $data['Email'] ?? null,
            'phone_no' => $data['Telephone'] ?? null,
            'coverBegins' => $data['CoverBegins'] ?? null,
            'coverEnds' => $data['CoverEnds'] ?? null,
            'countryId' => $data['CountryId'] ?? null,
            'countryId2' => $data['CountryId2'] ?? null,
            'purposeOfTravel' => $data['PurposeOfTravel'] ?? null,
            'travelPlanId' => $data['TravelPlanId'] ?? null,
            'bookingTypeId' => $data['BookingTypeId'] ?? null,
            'noOfPeople' => $data['NoOfPeople'] ?? null,
            'noOfChildren' => $data['NoOfChildren'] ?? null,
            'multiTrip' => $data['IsMultiTrip'] ?? false,
            'amount' => $data['Amount'],
            'amountA' => $data['AllianzPrice'] ?? null,
            'quoteId' => $data['quoteId'] ?? null,
            'requestdate' => $data['DateTimeAdded'] ?? now(),
        ]);
    }

    /**
     * Has Sanlam issue the policy, for one traveller or a family.
     *
     * @return string Sanlam's cover id
     */
    public function issuePolicy(array $form, array $nextOfKin): string
    {
        $family = (int) ($form['bookingTypeId'] ?? self::INDIVIDUAL) === self::FAMILY;
        $payload = $family ? $this->familyPayload($form, $nextOfKin) : $this->individualPayload($form, $nextOfKin);

        return Http::withToken($this->token())->acceptJson()
            ->post($this->baseUrl().'/Travel/api/'.($family ? 'FamilyBooking' : 'IndividualBooking'), $payload)
            ->throw()
            ->body();
    }

    /** The purchase as saved, from the booking forms. */
    public function purchaseAttributes(array $form, array $nextOfKin, string $reference, ?string $coverId, string $status): array
    {
        $bookingTypeId = (int) ($form['bookingTypeId'] ?? self::INDIVIDUAL);
        $family = $bookingTypeId === self::FAMILY;
        // A family's lead adult's fields end in 1
        $lead = fn (string $field) => $form[$family ? $field.'1' : $field] ?? null;

        return [
            'trans_id' => $reference,
            'ref_id' => $reference,
            'qoute_id' => $form['qouteId'] ?? null,
            'cover_id' => $coverId,
            'bookingtype_id' => $bookingTypeId,
            'c_amount' => $form['amount'] ?? null,
            'vat' => $nextOfKin['vat'] ?? null,
            't_amount' => $nextOfKin['p_amount'] ?? null,
            'payment_option' => 'seerbit',
            'surname' => $form['surname'] ?? null,
            'middlename' => $lead('middlename'),
            'firstname' => $form['firstname'] ?? null,
            'gender' => $lead('gender'),
            'title' => $lead('title'),
            'dob' => $lead('dob'),
            'email' => $form['email'] ?? null,
            'phone_no' => $form['phone_no'] ?? null,
            'state' => $form['state'] ?? null,
            'address' => $form['address'] ?? null,
            'zipcode' => $form['zipcode'] ?? null,
            'passport_no' => $form['passport_no'] ?? null,
            'nin' => $form['nin'] ?? null,
            'occupation' => $lead('ocupation'),
            'nationalty' => $lead('nationalty'),
            'marital_status' => $lead('marital_status'),
            'noc' => $form['noc'] ?? 0,
            'medicalCondition' => $form[$family ? 'MedicalCondition1' : 'MedicalCondition'] ?? null,
            'nok_fullname' => $nextOfKin['nok_fullname'] ?? null,
            'nok_address' => $nextOfKin['nok_address'] ?? null,
            'nok_phone' => $nextOfKin['nok_phone'] ?? null,
            'nok_relationship' => $nextOfKin['nok_relationship'] ?? null,
            'status' => $status,
        ];
    }

    /**
     * A policy booked before payment, which staff do for a customer. Every
     * traveller's details are kept so the policy can be issued once paid.
     */
    public function createPending(InsuranceQuote $quote, array $form, array $nextOfKin): InsurancePurchase
    {
        $price = $this->price($quote);
        $form = array_merge($form, ['qouteId' => $quote->quoteRequestId, 'bookingTypeId' => $quote->bookingTypeId, 'amount' => $price['amount']]);
        $nextOfKin = array_merge($nextOfKin, ['c_amount' => $price['amount'], 'vat' => $price['vat'], 'p_amount' => $price['total']]);

        return InsurancePurchase::create($this->purchaseAttributes($form, $nextOfKin, 'INS'.strtoupper(bin2hex(random_bytes(6))), null, 'Pending') + [
            'booking_form' => ['form' => $form, 'next_of_kin' => $nextOfKin],
        ]);
    }

    /**
     * Once a staff booking is paid: issues the policy, once however many times
     * it's called. If Sanlam fails, the purchase is "Failed" (paid, no policy)
     * for customer support, as on the website.
     */
    public function completePayment(InsurancePurchase $purchase, ?string $paymentOption = null): void
    {
        $justPaid = DB::transaction(function () use ($purchase, $paymentOption): bool {
            $locked = InsurancePurchase::query()->lockForUpdate()->findOrFail($purchase->getKey());

            if ($locked->status !== 'Pending' && $locked->status !== 'Cancelled') {
                return false;
            }

            $locked->update(array_filter(['status' => 'Successful', 'payment_option' => $paymentOption]));

            return true;
        });

        $purchase->refresh();

        if (! $justPaid) {
            return;
        }

        try {
            $purchase->update(['cover_id' => $this->issuePolicy($purchase->booking_form['form'], $purchase->booking_form['next_of_kin'])]);
            app(BookingThankYou::class)->send($purchase->email, $purchase->firstname, 'insurance');
        } catch (\Throwable $e) {
            Log::error('Sanla booking error: '.$e->getMessage(), ['reference' => $purchase->trans_id]);
            $purchase->update(['status' => 'Failed']);
        }

        $this->notifyReservations($purchase->fresh());
    }

    /** Email the reservations team; never let a mail problem break anything else. */
    public function notifyReservations(InsurancePurchase $purchase): void
    {
        try {
            Mail::to(config('travelwheel.reservations_email'))->send(new InsurancePurchaseNotificationMail($purchase));
        } catch (\Throwable $e) {
            Log::error('Insurance reservation notification failed', ['reference' => $purchase->trans_id, 'error' => $e->getMessage()]);
        }
    }

    private function individualPayload(array $form, array $nextOfKin): array
    {
        return [
            'QuoteId' => $form['qouteId'],
            'Surname' => $form['surname'],
            'MiddleName' => $form['middlename'] ?? null,
            'FirstName' => $form['firstname'],
            'GenderId' => $form['gender'],
            'TitleId' => $form['title'],
            'DateOfBirth' => $this->sanlamDate($form['dob']),
            'Email' => $form['email'],
            'Telephone' => $form['phone_no'],
            'StateId' => $form['state'],
            'Address' => $form['address'],
            'ZipCode' => $form['zipcode'],
            'Nationality' => $form['nationalty'],
            'PassportNo' => $form['passport_no'],
            'IdentificationPath' => null,
            'Occupation' => $form['ocupation'],
            'Nin' => $form['nin'] ?? null,
            'MaritalStatusId' => $form['marital_status'],
            'PreExistingMedicalCondition' => false,
            'MedicalCondition' => null,
            'NextOfKin' => $this->nextOfKinPayload($nextOfKin),
        ];
    }

    private function familyPayload(array $form, array $nextOfKin): array
    {
        $nok = $this->nextOfKinPayload($nextOfKin);
        $shared = fn (array $traveller) => $traveller + [
            'StateId' => $form['state'],
            'Address' => $form['address'],
            'ZipCode' => $form['zipcode'],
            'IdentificationPath' => null,
            'PreExistingMedicalCondition' => false,
            'MedicalCondition' => null,
            'NextOfKin' => $nok,
        ];

        $payload = [
            $shared([
                'QuoteId' => $form['qouteId'],
                'Surname' => $form['surname'],
                'MiddleName' => $form['middlename1'] ?? '',
                'FirstName' => $form['firstname'],
                'GenderId' => $form['gender1'],
                'TitleId' => $form['title1'],
                'DateOfBirth' => $this->sanlamDate($form['dob1']),
                'Email' => $form['email'],
                'Telephone' => $form['phone_no'],
                'Nationality' => $form['nationalty1'],
                'PassportNo' => $form['passport_no'],
                'Occupation' => $form['ocupation1'],
                'MaritalStatusId' => $form['marital_status1'],
                'Nin' => $form['nin'] ?? null,
            ]),
            $shared([
                'QuoteId' => $form['qouteId'],
                'Surname' => $form['surname'],
                'MiddleName' => $form['middlename2'] ?? '',
                'FirstName' => $form['firstname2'],
                'GenderId' => $form['gender2'],
                'TitleId' => $form['title2'],
                'DateOfBirth' => $this->sanlamDate($form['dob2']),
                'Email' => $form['email2'],
                'Telephone' => $form['phone_no2'],
                'Nationality' => $form['nationalty2'],
                'PassportNo' => $form['passport_no2'] ?? '',
                'Occupation' => $form['ocupation2'],
                // The website sends the lead's marital status for both adults
                'MaritalStatusId' => $form['marital_status1'],
                'Nin' => $form['nin2'] ?? null,
            ]),
        ];

        for ($i = 1; $i <= (int) ($form['noc'] ?? 0); $i++) {
            $dob = $form['dobC'.$i] ?? null;
            $child = $shared([
                'QuoteId' => $form['qouteId'],
                'Surname' => $form['surname'],
                'MiddleName' => $form['middlenameC'.$i] ?? '',
                'FirstName' => $form['firstnameC'.$i] ?? '',
                'GenderId' => $form['genderC'.$i] ?? 1,
                'TitleId' => $form['titleC'.$i] ?? 18,
                'DateOfBirth' => $dob ? $this->sanlamDate($dob) : '',
                'Email' => $form['email'],
                'Telephone' => $form['phone_no'],
                'Nationality' => $form['nationaltyC'.$i] ?? '',
                'PassportNo' => $form['passport_noC'.$i] ?? '',
                'Occupation' => 'Student',
                'MaritalStatusId' => 1,
                'Nin' => $form['ninC'.$i] ?? null,
            ]);
            // Sanlam's family booking has always been sent StateID for children
            $child['StateID'] = $child['StateId'];
            unset($child['StateId']);
            $payload[] = $child;
        }

        return $payload;
    }

    private function nextOfKinPayload(array $nextOfKin): array
    {
        return [
            'FullName' => $nextOfKin['nok_fullname'] ?? '',
            'Address' => $nextOfKin['nok_address'] ?? '',
            'Relationship' => $nextOfKin['nok_relationship'] ?? '',
            'Telephone' => $nextOfKin['nok_phone'] ?? '',
        ];
    }

    private function token(): string
    {
        $token = Http::asForm()->post($this->baseUrl().'/Travel/token', [
            'username' => config('services.sanlam.username'),
            'password' => config('services.sanlam.password'),
            'grant_type' => 'password',
        ])->json('access_token');

        if (empty($token)) {
            throw new RuntimeException('Sanla authentication failed.');
        }

        return $token;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.sanlam.base_url', 'https://web-app.sanlamallianz.com.ng'), '/');
    }

    private function sanlamDate(string $date): string
    {
        return Carbon::createFromFormat('Y-m-d', $date)->format('d-M-Y');
    }
}
