<?php

namespace App\Http\Controllers\Business;

use App\Filament\Resources\Companies\CompanyResource;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Corporate\CorporateContext;
use Illuminate\Http\RedirectResponse;

/** A member of staff finishing booking for a company. */
class ActingController extends Controller
{
    public function stop(CorporateContext $context): RedirectResponse
    {
        if (! $context->hasActingSession()) {
            return redirect()->route('air');
        }

        $company = $context->actingCompany();
        $context->stopActing();

        if (! $company) {
            return redirect()->route('air');
        }

        if (auth()->check()) {
            ActivityLog::record('corporate.stopped_booking_for_company', 'Stopped booking for '.$company->name, $company);
        }

        return redirect()->to(CompanyResource::getUrl('view', ['record' => $company], panel: 'admin'));
    }
}
