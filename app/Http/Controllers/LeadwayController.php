<?php

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LeadwayController extends Controller
{
    private function apiKey(): string
    {
        return hash('sha512', 'Pxc0Vu8i5AD1o3WPH3gXeDUt+7E6nb/yZgcahpdo1Nl92NcuCZgZFLuC6v40aXzU');
    }

    private function headers(): array
    {
        return [
            'Authorization' => $this->apiKey(),
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        ];
    }

    public function insuranceLeadway()
    {
        try {
            $client   = new Client();
            $response = $client->request('GET', 'https://mc.leadway.com/interBusinessConnection/interBusinessConnection.svc/general/getTravelProducts/', [
                'headers' => $this->headers(),
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return view('air.insurance.insuranceLeadway', compact('data'));
        } catch (\Exception $e) {
            Log::error('Leadway products error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load Leadway products. Please try again.');
        }
    }

    public function insuranceLeadwayP(Request $request)
    {
        $packageCode = $request->query('prodCode');
        $parts       = explode(' - ', $packageCode, 2);
        $prodCode    = $parts[0] ?? $packageCode;
        $prodName    = $parts[1] ?? $packageCode;

        session(['prodName' => $prodName]);

        try {
            $client   = new Client();
            $response = $client->request('GET', 'https://mc.leadway.com/interBusinessConnection/interBusinessConnection.svc/general/getCountriesForTravel/' . $prodCode, [
                'headers' => $this->headers(),
            ]);
            $data = json_decode($response->getBody()->getContents(), true);
            return view('air.insurance.insuranceLeadwayP', compact('data', 'prodCode', 'prodName'));
        } catch (\Exception $e) {
            Log::error('Leadway countries error: ' . $e->getMessage());
            return back()->with('error', 'Unable to load country data. Please try again.');
        }
    }

    public function insuranceLeadwayQ(Request $request)
    {
        $dataform = $request->all();
        Session::put('insurance_data_form', $dataform);

        $formattedDob = date('m-d-Y', strtotime($dataform['dob']));
        $formattedDpd = date('m-d-Y', strtotime($dataform['begin_date']));
        $formattedArd = date('m-d-Y', strtotime($dataform['end_date']));

        $payload = [
            'productCode' => $dataform['prodCode'],
            'clientInfo'  => [
                'surname'       => $dataform['surname'],
                'othername'     => $dataform['othername'],
                'middlename'    => $dataform['lastname'],
                'emailAddress'  => $dataform['email'],
                'mobileNo'      => $dataform['phone_no'],
                'gender'        => $dataform['gender'],
                'dob_mm_dd_yyyy'=> $formattedDob,
                'postalAddress' => $dataform['address'],
                'city'          => $dataform['city'],
                'state'         => $dataform['state'],
            ],
            'clientNo'    => '',
            'destination' => $dataform['country'],
            'depaturedate'=> $formattedDpd,
            'arrivaldate' => $formattedArd,
            'passportNo'  => $dataform['passport_no'],
        ];

        try {
            $client   = new Client();
            $response = $client->request('POST', 'https://mc.leadway.com/interBusinessConnection/interBusinessConnection.svc/quotation/travelInsurance/', [
                'headers' => $this->headers(),
                'json'    => $payload,
            ]);

            $data     = json_decode($response->getBody()->getContents(), true);
            $errorMsg = '';

            if (!empty($data['errorMsg'])) {
                $errorData = json_decode($data['errorMsg'], true);
                $errorMsg  = (json_last_error() === JSON_ERROR_NONE && isset($errorData['error_description']))
                    ? $errorData['error'] . ' ' . $errorData['error_description']
                    : 'Unknown error';
            }

            return view('air.insurance.insuranceLeadwayQ', compact('data', 'dataform', 'errorMsg'));

        } catch (RequestException $e) {
            Log::error('Leadway quote error: ' . $e->getMessage());
            return back()->with('error', 'Could not retrieve quote. Please try again.');
        }
    }

    public function makePurchase(Request $request)
    {
        $dataform2 = $request->all();

        $payload = [
            'quoteNo'            => $dataform2['quoteNo'],
            'paymentReferenceNo' => '',
            'issueCreditNote'    => true,
            'creditNote'         => $dataform2['quoteNo'],
            'callbackUrl'        => route('air.insurance'),
        ];

        try {
            $client   = new Client();
            $response = $client->request('POST', 'https://mc.leadway.com/interBusinessConnection/interBusinessConnection.svc/quotation/payforQuotation/', [
                'headers' => $this->headers(),
                'json'    => $payload,
                'timeout' => 60,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if ($response->getStatusCode() === 200 && !empty($data['paystackpaymentUrl'])) {
                return redirect()->away($data['paystackpaymentUrl']);
            }

            return back()->with('error', 'Payment failed. Please try again.');

        } catch (\Exception $e) {
            Log::error('Leadway makePurchase error: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}
