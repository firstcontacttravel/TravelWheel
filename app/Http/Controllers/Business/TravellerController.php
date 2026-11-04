<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Livewire\Pages\FlightBooking;
use App\Models\CompanyTraveller;
use App\Models\CompanyUser;
use App\Services\Corporate\CompanyTravellers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A company's saved travellers. Everyone at the company can use and edit them,
 * and only Admins can delete. Every lookup goes through the signed-in person's
 * company, so another company's ids answer 404.
 */
class TravellerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('business.travellers.index', [
            'travellers' => $this->user($request)->company->travellers()
                ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner
                    ->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')))
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->paginate(25)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('business.travellers.form', ['traveller' => null, 'nationalities' => $this->nationalities()]);
    }

    public function store(Request $request, CompanyTravellers $travellers): RedirectResponse
    {
        $travellers->create($this->user($request), $this->validated($request));

        return redirect()->route('business.travellers.index')->with('status', 'Traveller saved.');
    }

    public function edit(Request $request, int $traveller): View
    {
        return view('business.travellers.form', [
            'traveller' => $this->find($request, $traveller),
            'nationalities' => $this->nationalities(),
        ]);
    }

    public function update(Request $request, CompanyTravellers $travellers, int $traveller): RedirectResponse
    {
        $found = $this->find($request, $traveller);
        $travellers->update($found, $this->user($request), $this->validated($request));

        return redirect()->route('business.travellers.index')->with('status', 'Traveller updated.');
    }

    public function destroy(Request $request, CompanyTravellers $travellers, int $traveller): RedirectResponse
    {
        abort_unless($this->user($request)->isCompanyAdmin(), 403);

        $travellers->delete($this->find($request, $traveller), $this->user($request));

        return redirect()->route('business.travellers.index')->with('status', 'Traveller deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', Rule::in(['Mr', 'Mrs', 'Ms', 'Miss', 'Dr', 'Master'])],
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'gender' => ['required', Rule::in(['M', 'F'])],
            'dob' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'size:2'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'frequent_flyer_number' => ['nullable', 'string', 'max:30'],
            'passport_no' => ['nullable', 'string', 'min:5', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'passport_issue_country' => ['nullable', 'string', 'size:2'],
            'passport_issue_date' => ['nullable', 'date', 'before:today'],
            'passport_exp' => ['nullable', 'date'],
        ]);
    }

    private function nationalities(): array
    {
        return (new FlightBooking)->nationalities;
    }

    private function find(Request $request, int $id): CompanyTraveller
    {
        return $this->user($request)->company->travellers()->findOrFail($id);
    }

    private function user(Request $request): CompanyUser
    {
        return $request->attributes->get('companyUser');
    }
}
