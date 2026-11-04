<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Corporate\CorporateAccounts;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/** The public "request a corporate account" form. */
class CompanyRequestController extends Controller
{
    public function create(): View
    {
        return view('business.request.create');
    }

    /**
     * Every outcome ends on the same page, whether the company is new, already
     * on file or a bot, so the form can't be used to find out who has an account.
     */
    public function store(Request $request, CorporateAccounts $accounts): RedirectResponse
    {
        if (filled($request->input('hp_check'))) {
            Log::info('Corporate request spam trap triggered', ['ip' => $request->ip()]);

            return redirect()->route('business.request.received');
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'cac_number' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9][A-Za-z0-9 \-]*$/'],
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:190'],
            'contact_phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+() \-]+$/'],
        ], [
            'cac_number.regex' => 'Enter the CAC number using letters and numbers, for example RC123456.',
        ]);

        $accounts->submitRequest($data);

        return redirect()->route('business.request.received');
    }

    public function received(): View
    {
        return view('business.request.received');
    }
}
