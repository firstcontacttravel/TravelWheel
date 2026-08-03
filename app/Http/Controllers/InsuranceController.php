<?php

namespace App\Http\Controllers;

use App\Models\InsurancePurchase;
use App\Models\InsuranceQuote;
use App\Services\SeerbitPaymentService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class InsuranceController extends Controller
{
    private function sanlaToken(): string
    {
        $response = Http::asForm()->post('https://web-app.sanlamallianz.com.ng/Travel/token', [
            'username'   => 'lightaydot@yahoo.com',
            'password'   => 'Travelwheel@15',
            'grant_type' => 'password',
        ]);
        $data = $response->json();
        if (empty($data['access_token'])) {
            throw new \RuntimeException('Sanla authentication failed.');
        }
        return $data['access_token'];
    }

    public function makeRequestQuote(Request $request)
    {
        $dataform = $request->all();

        $TravelPlanId = ($dataform['travel_plan'] === 'Schengen') ? 1 : 2;

        $dateOfBirth = Carbon::createFromFormat('Y-m-d', $dataform['dob'])->format('d-M-Y');
        $coverDate   = Carbon::createFromFormat('Y-m-d', $dataform['begin_date'])->format('d-M-Y');
        $endDate     = Carbon::createFromFormat('Y-m-d', $dataform['end_date'])->format('d-M-Y');

        try {
            $token = $this->sanlaToken();

            $payload = [
                'DateOfBirth'     => $dateOfBirth,
                'Email'           => $dataform['email'],
                'Telephone'       => $dataform['phone_no'],
                'CoverBegins'     => $coverDate,
                'CoverEnds'       => $endDate,
                'CountryId'       => $dataform['country'],
                'PurposeOfTravel' => $dataform['purpose_of_travel'],
                'TravelPlanId'    => $TravelPlanId,
                'BookingTypeId'   => $dataform['booking_type'],
                'IsRoundTrip'     => false,
                'NoOfPeople'      => $dataform['nop'] ?? 1,
                'NoOfChildren'    => $dataform['noc'] ?? 0,
                'IsMultiTrip'     => false,
            ];

            $client   = new Client();
            $response = $client->request('POST', 'https://web-app.sanlamallianz.com.ng/Travel/api/Quote', [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                'json'    => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            $quote = InsuranceQuote::create([
                'quoteRequestId'  => $data['QuoteRequestId'],
                'productVariantId'=> $data['ProductVariantId'],
                'dob'             => $data['DateOfBirth'],
                'email'           => $data['Email'],
                'phone_no'        => $data['Telephone'],
                'coverBegins'     => $data['CoverBegins'],
                'coverEnds'       => $data['CoverEnds'],
                'countryId'       => $data['CountryId'],
                'countryId2'      => $data['CountryId2'] ?? null,
                'purposeOfTravel' => $data['PurposeOfTravel'],
                'travelPlanId'    => $data['TravelPlanId'],
                'bookingTypeId'   => $data['BookingTypeId'],
                'noOfPeople'      => $data['NoOfPeople'],
                'noOfChildren'    => $data['NoOfChildren'],
                'multiTrip'       => $data['IsMultiTrip'] ?? false,
                'amount'          => $data['Amount'],
                'amountA'         => $data['AllianzPrice'] ?? null,
                'quoteId'         => $data['quoteId'],
                'requestdate'     => $data['DateTimeAdded'] ?? now(),
            ]);

            return redirect()->route('air.insurance.quote.show', ['qid' => $quote->id]);

        } catch (RequestException $e) {
            Log::error('Sanla quote error: ' . $e->getMessage());
            return back()->with('error', 'Could not retrieve quote. Please try again.');
        } catch (\Exception $e) {
            Log::error('Sanla quote error: ' . $e->getMessage());
            return back()->with('error', 'Could not retrieve quote. Please try again.');
        }
    }

    public function insuranceRequest(Request $request)
    {
        $qid = $request->query('qid');
        $quote = InsuranceQuote::findOrFail($qid);

        if ((int) $quote->bookingTypeId === 2) {
            return view('air.insurance.insurancePurchaseF', compact('quote'));
        }

        return view('air.insurance.insurancePurchase', compact('quote'));
    }

    public function insurancePurchase(Request $request)
    {
        $dataform = $request->all();
        Session::put('insurance_data_form', $dataform);

        return view('air.insurance.insurancePurchaseN', compact('dataform'));
    }

    public function makeRequestPurchase(Request $request)
    {
        $dataform2 = $request->all();
        Session::put('insurance_data_form2', $dataform2);

        $dataform  = Session::get('insurance_data_form');
        if (!$dataform) {
            return back()->with('error', 'Session expired. Please rebook.');
        }

        $fullname  = trim(($dataform['surname'] ?? '') . ' ' . ($dataform['firstname'] ?? ''));
        $reference = 'INS' . strtoupper(bin2hex(random_bytes(6)));
        $amount    = (float) ($dataform2['p_amount'] ?? 0);
        $phone     = preg_replace('/\D/', '', '+234' . ltrim($dataform['phone_no'] ?? '', '0'));

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->initializePayment([
            'amount'             => (string) $amount,
            'currency'           => 'NGN',
            'email'              => $dataform['email'] ?? '',
            'fullName'           => $fullname,
            'mobileNumber'       => $phone,
            'callbackUrl'        => route('air.insurance.callback'),
            'paymentReference'   => $reference,
            'productId'          => 'INS' . $reference,
            'productDescription' => 'Travel Insurance Booking',
        ]);

        return redirect($result['redirect_link']);
    }

    public function callbackSeerbit(Request $request)
    {
        // SeerBit's hosted checkout redirects back with `reference` in some modes,
        // not just `paymentReference` — check both (see ProtocolController::callbackSeerbit).
        $paymentReference = $request->query('paymentReference')
            ?? $request->input('paymentReference')
            ?? $request->query('reference')
            ?? $request->input('reference')
            ?? $request->query('payRef')
            ?? $request->input('payRef');

        if (!$paymentReference) {
            return redirect()->route('air.insurance')->with('error', 'Payment reference missing.');
        }

        $seerbit = new SeerbitPaymentService();
        $result  = $seerbit->verifyPayment($paymentReference);
        if (!$result['ok']) {
            return redirect()->route('air.insurance')->with('error', 'Payment was not successful.');
        }

        if (InsurancePurchase::where('trans_id', $paymentReference)->exists()) {
            return redirect()->route('air.insurance.success');
        }

        $dataform  = Session::get('insurance_data_form');
        $dataform2 = Session::get('insurance_data_form2');

        if (!$dataform || !$dataform2) {
            return redirect()->route('air.insurance')->with('error', 'Session expired. Please rebook.');
        }

        try {
            $token = $this->sanlaToken();
            $bookingTypeId = (int) ($dataform['bookingTypeId'] ?? 1);

            if ($bookingTypeId === 2) {
                $coverId = $this->familyBooking($token, $dataform, $dataform2);
            } else {
                $coverId = $this->individualBooking($token, $dataform, $dataform2);
            }

            InsurancePurchase::create([
                'trans_id'       => $paymentReference,
                'ref_id'         => $paymentReference,
                'qoute_id'       => $dataform['qouteId'] ?? null,
                'cover_id'       => $coverId,
                'bookingtype_id' => $bookingTypeId,
                'c_amount'       => $dataform['amount'] ?? null,
                'vat'            => $dataform2['vat'] ?? null,
                't_amount'       => $dataform2['p_amount'] ?? null,
                'payment_option' => 'seerbit',
                'surname'        => $dataform['surname'] ?? null,
                'middlename'     => $bookingTypeId === 2 ? ($dataform['middlename1'] ?? null) : ($dataform['middlename'] ?? null),
                'firstname'      => $dataform['firstname'] ?? null,
                'gender'         => $bookingTypeId === 2 ? ($dataform['gender1'] ?? null) : ($dataform['gender'] ?? null),
                'title'          => $bookingTypeId === 2 ? ($dataform['title1'] ?? null) : ($dataform['title'] ?? null),
                'dob'            => $bookingTypeId === 2 ? ($dataform['dob1'] ?? null) : ($dataform['dob'] ?? null),
                'email'          => $dataform['email'] ?? null,
                'phone_no'       => $dataform['phone_no'] ?? null,
                'state'          => $dataform['state'] ?? null,
                'address'        => $dataform['address'] ?? null,
                'zipcode'        => $dataform['zipcode'] ?? null,
                'passport_no'    => $dataform['passport_no'] ?? null,
                'nin'            => $dataform['nin'] ?? null,
                'occupation'     => $bookingTypeId === 2 ? ($dataform['ocupation1'] ?? null) : ($dataform['ocupation'] ?? null),
                'nationalty'     => $bookingTypeId === 2 ? ($dataform['nationalty1'] ?? null) : ($dataform['nationalty'] ?? null),
                'marital_status' => $bookingTypeId === 2 ? ($dataform['marital_status1'] ?? null) : ($dataform['marital_status'] ?? null),
                'noc'            => $dataform['noc'] ?? 0,
                'medicalCondition' => $bookingTypeId === 2 ? ($dataform['MedicalCondition1'] ?? null) : ($dataform['MedicalCondition'] ?? null),
                'nok_fullname'   => $dataform2['fullname'] ?? null,
                'nok_address'    => $dataform2['address'] ?? null,
                'nok_phone'      => $dataform2['phone_no'] ?? null,
                'nok_relationship' => $dataform2['relationship'] ?? null,
                'status'         => 'Successful',
            ]);

            Session::forget(['insurance_data_form', 'insurance_data_form2']);
            return redirect()->route('air.insurance.success');

        } catch (\Exception $e) {
            Log::error('Sanla booking error: ' . $e->getMessage());
            return redirect()->route('air.insurance.success');
        }
    }

    private function individualBooking(string $token, array $dataform, array $dataform2): string
    {
        $dateOfBirth = Carbon::createFromFormat('Y-m-d', $dataform['dob'])->format('d-M-Y');

        $payload = [
            'QuoteId'                    => $dataform['qouteId'],
            'Surname'                    => $dataform['surname'],
            'MiddleName'                 => $dataform['middlename'],
            'FirstName'                  => $dataform['firstname'],
            'GenderId'                   => $dataform['gender'],
            'TitleId'                    => $dataform['title'],
            'DateOfBirth'                => $dateOfBirth,
            'Email'                      => $dataform['email'],
            'Telephone'                  => $dataform['phone_no'],
            'StateId'                    => $dataform['state'],
            'Address'                    => $dataform['address'],
            'ZipCode'                    => $dataform['zipcode'],
            'Nationality'                => $dataform['nationalty'],
            'PassportNo'                 => $dataform['passport_no'],
            'IdentificationPath'         => null,
            'Occupation'                 => $dataform['ocupation'],
            'Nin'                        => $dataform['nin'] ?? null,
            'MaritalStatusId'            => $dataform['marital_status'],
            'PreExistingMedicalCondition'=> false,
            'MedicalCondition'           => null,
            'NextOfKin'                  => [
                'FullName'     => $dataform2['fullname'],
                'Address'      => $dataform2['address'],
                'Relationship' => $dataform2['relationship'],
                'Telephone'    => $dataform2['phone_no'],
            ],
        ];

        $client   = new Client();
        $response = $client->request('POST', 'https://web-app.sanlamallianz.com.ng/Travel/api/IndividualBooking', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
            'json'    => $payload,
        ]);

        return $response->getBody()->getContents();
    }

    private function familyBooking(string $token, array $dataform, array $dataform2): string
    {
        $dob1 = Carbon::createFromFormat('Y-m-d', $dataform['dob1'])->format('d-M-Y');
        $dob2 = Carbon::createFromFormat('Y-m-d', $dataform['dob2'])->format('d-M-Y');

        $nokPayload = [
            'FullName'     => $dataform2['fullname'],
            'Address'      => $dataform2['address'],
            'Relationship' => $dataform2['relationship'],
            'Telephone'    => $dataform2['phone_no'],
        ];

        $payload = [
            [
                'QuoteId'    => $dataform['qouteId'],
                'Surname'    => $dataform['surname'],
                'MiddleName' => $dataform['middlename1'] ?? '',
                'FirstName'  => $dataform['firstname'],
                'GenderId'   => $dataform['gender1'],
                'TitleId'    => $dataform['title1'],
                'DateOfBirth'=> $dob1,
                'Email'      => $dataform['email'],
                'Telephone'  => $dataform['phone_no'],
                'StateId'    => $dataform['state'],
                'Address'    => $dataform['address'],
                'ZipCode'    => $dataform['zipcode'],
                'Nationality'=> $dataform['nationalty1'],
                'PassportNo' => $dataform['passport_no'],
                'IdentificationPath' => null,
                'Occupation' => $dataform['ocupation1'],
                'MaritalStatusId' => $dataform['marital_status1'],
                'PreExistingMedicalCondition' => false,
                'MedicalCondition' => null,
                'Nin'        => $dataform['nin'] ?? null,
                'NextOfKin'  => $nokPayload,
            ],
            [
                'QuoteId'    => $dataform['qouteId'],
                'Surname'    => $dataform['surname'],
                'MiddleName' => $dataform['middlename2'] ?? '',
                'FirstName'  => $dataform['firstname2'],
                'GenderId'   => $dataform['gender2'],
                'TitleId'    => $dataform['title2'],
                'DateOfBirth'=> $dob2,
                'Email'      => $dataform['email2'],
                'Telephone'  => $dataform['phone_no2'],
                'StateId'    => $dataform['state'],
                'Address'    => $dataform['address'],
                'ZipCode'    => $dataform['zipcode'],
                'Nationality'=> $dataform['nationalty2'],
                'PassportNo' => $dataform['passport_no2'] ?? '',
                'IdentificationPath' => null,
                'Occupation' => $dataform['ocupation2'],
                'MaritalStatusId' => $dataform['marital_status1'],
                'PreExistingMedicalCondition' => false,
                'MedicalCondition' => null,
                'Nin'        => $dataform['nin2'] ?? null,
                'NextOfKin'  => $nokPayload,
            ],
        ];

        // Append children
        $totalChildren = (int) ($dataform['noc'] ?? 0);
        for ($i = 1; $i <= $totalChildren; $i++) {
            $dobC = $dataform['dobC' . $i] ?? null;
            $payload[] = [
                'QuoteId'    => $dataform['qouteId'],
                'Surname'    => $dataform['surname'],
                'MiddleName' => $dataform['middlenameC' . $i] ?? '',
                'FirstName'  => $dataform['firstnameC' . $i] ?? '',
                'GenderId'   => $dataform['genderC' . $i] ?? 1,
                'TitleId'    => $dataform['titleC' . $i] ?? 18,
                'DateOfBirth'=> $dobC ? Carbon::createFromFormat('Y-m-d', $dobC)->format('d-M-Y') : '',
                'Email'      => $dataform['email'],
                'Telephone'  => $dataform['phone_no'],
                'StateID'    => $dataform['state'],
                'Address'    => $dataform['address'],
                'ZipCode'    => $dataform['zipcode'],
                'Nationality'=> $dataform['nationaltyC' . $i] ?? '',
                'PassportNo' => $dataform['passport_noC' . $i] ?? '',
                'IdentificationPath' => null,
                'Occupation' => 'Student',
                'MaritalStatusId' => 1,
                'PreExistingMedicalCondition' => false,
                'MedicalCondition' => null,
                'Nin'        => $dataform['ninC' . $i] ?? null,
                'NextOfKin'  => $nokPayload,
            ];
        }

        $client   = new Client();
        $response = $client->request('POST', 'https://web-app.sanlamallianz.com.ng/Travel/api/FamilyBooking', [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
            'json'    => $payload,
        ]);

        return $response->getBody()->getContents();
    }

    public function insuranceSuccess()
    {
        return view('air.insurance.insurancesucces');
    }
}
