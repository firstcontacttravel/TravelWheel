<?php

namespace App\Http\Controllers;

use App\Models\VisaApplication;
use App\Models\VisaProduct;
use App\Services\VisaApplicationDraftService;
use App\Services\VisaFunnelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisaApplicationController extends Controller
{
    public function start(Request $request, VisaApplicationDraftService $drafts, VisaFunnelService $funnel): RedirectResponse
    {
        $validated = $request->validate(['visa_product_id' => ['required', 'integer', 'exists:visa_products,id']]);
        $results = collect(session('visaResultsStore', []));
        $eligibleIds = $results->whereIn('eligibility.status', ['eligible', 'conditionally_eligible'])->pluck('id')->all();
        $search = session('visaSearchParamsStore');

        if (! is_array($search) || $search === []) {
            return redirect()->route('air.visa')->withErrors(['visa' => 'Your visa search has expired. Please search again.']);
        }

        [$application, $token] = $drafts->start(VisaProduct::query()->findOrFail($validated['visa_product_id']), $search, $eligibleIds);
        session()->put("visa_application_access.{$application->reference}", true);
        session()->put("visa_application_resume_tokens.{$application->reference}", $token);
        $funnel->record('application_started', [], $application, 'application_started|'.$application->id);

        return redirect()->route('visa.application', $application);
    }

    public function resume(VisaApplication $application, string $token, VisaApplicationDraftService $drafts): RedirectResponse
    {
        abort_unless($drafts->authorize($application, $token), 403);

        return redirect()->route('visa.application', $application);
    }
}
