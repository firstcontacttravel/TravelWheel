<?php

namespace App\Services\Corporate;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\CompanyTraveller;
use App\Models\CompanyUser;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/**
 * Saved travellers, and joining them to a booking.
 *
 * Checkout sends only a traveller's id. The full passport is added here, on the
 * server, as the booking is submitted, and only after checking the traveller
 * belongs to the company. Logs record which fields changed, never their values.
 */
class CompanyTravellers
{
    private const PASSPORT_FIELDS = ['passport_no', 'passport_issue_country', 'passport_issue_date', 'passport_exp'];

    /** What the checkout form may hold. Nothing here reveals a passport number. */
    public function checkoutFields(CompanyTraveller $traveller): array
    {
        return [
            'title' => $traveller->title,
            'first_name' => $traveller->first_name,
            'middle_name' => (string) $traveller->middle_name,
            'last_name' => $traveller->last_name,
            'gender' => $traveller->gender,
            'dob' => $traveller->dob,
            'nationality' => $traveller->nationality,
            'frequent_flyer_number' => (string) $traveller->frequent_flyer_number,
            'saved_traveller_id' => $traveller->id,
            'passport_masked' => (string) $traveller->maskedPassport(),
            'save_traveller' => false,
        ] + array_fill_keys(self::PASSPORT_FIELDS, '');
    }

    /**
     * Swaps saved-traveller ids for full details, for a signed-in company. The
     * public can't use these fields, so they're removed.
     *
     * A passport number typed at checkout is for this trip, and replaces the
     * saved one as a set. Otherwise the saved one is used.
     *
     * @throws ValidationException when the id isn't one of this company's travellers
     */
    public function fillBooking(array $validated, ?CompanyUser $user): array
    {
        foreach ($validated['passengers'] as $index => $passenger) {
            $id = $passenger['saved_traveller_id'] ?? null;

            if ($user && filled($id)) {
                $traveller = $user->company->travellers()->find($id);

                if (! $traveller) {
                    throw ValidationException::withMessages([
                        "passengers.{$index}.saved_traveller_id" => 'That saved traveller could not be found.',
                    ]);
                }

                if (blank($passenger['passport_no'] ?? null) && $traveller->hasPassport()) {
                    $passenger = array_merge($passenger, Arr::only((array) $traveller->passport, self::PASSPORT_FIELDS));
                }
            } else {
                unset($passenger['saved_traveller_id']);
            }

            if (! $user) {
                unset($passenger['save_traveller']);
            }

            $validated['passengers'][$index] = $passenger;
        }

        if (! $user) {
            unset($validated['cost_code']);
        }

        return $validated;
    }

    /**
     * Saves anyone ticked "save for next time", once the booking has passed its
     * checks. Someone already saved is linked rather than saved twice, and their
     * profile is left as it was.
     */
    public function saveTicked(array $validated, CompanyUser $user): array
    {
        foreach ($validated['passengers'] as $index => $passenger) {
            $ticked = filter_var($passenger['save_traveller'] ?? false, FILTER_VALIDATE_BOOLEAN);
            unset($validated['passengers'][$index]['save_traveller']);

            if (! $ticked || filled($passenger['saved_traveller_id'] ?? null)) {
                continue;
            }

            $traveller = $this->findSamePerson($user->company, $passenger)
                ?? $this->create($user, $this->fromPassenger($passenger));

            $validated['passengers'][$index]['saved_traveller_id'] = $traveller->id;
        }

        return $validated;
    }

    public function create(CompanyUser $user, array $data): CompanyTraveller
    {
        $traveller = $user->company->travellers()->create(array_merge(
            $this->attributes($data),
            ['created_by_company_user_id' => $user->id],
        ));

        ActivityLog::recordForCompanyUser($user, 'company_traveller_created', 'Saved a traveller: '.$traveller->fullName(), $traveller);

        return $traveller;
    }

    /**
     * A blank passport number keeps the one on file: nobody can read it to send
     * it back. A new number can be entered, and the dates can always change.
     */
    public function update(CompanyTraveller $traveller, CompanyUser $user, array $data): void
    {
        $before = $traveller->only(['title', 'first_name', 'middle_name', 'last_name', 'gender', 'nationality', 'email', 'phone', 'dob', 'frequent_flyer_number']);
        $passportBefore = (array) $traveller->passport;

        if (blank($data['passport_no'] ?? null)) {
            $data['passport_no'] = $passportBefore['passport_no'] ?? null;
        }

        $traveller->update($this->attributes($data));

        $changed = array_keys(array_diff_assoc(
            array_map(fn ($value) => (string) $value, $traveller->only(array_keys($before))),
            array_map(fn ($value) => (string) $value, $before),
        ));

        foreach (self::PASSPORT_FIELDS as $field) {
            if ((string) ($passportBefore[$field] ?? '') !== (string) ($data[$field] ?? '')) {
                $changed[] = $field;
            }
        }

        ActivityLog::recordForCompanyUser($user, 'company_traveller_updated', 'Updated a saved traveller: '.$traveller->fullName(), $traveller, ['fields_changed' => array_values($changed)]);
    }

    public function delete(CompanyTraveller $traveller, CompanyUser $user): void
    {
        ActivityLog::recordForCompanyUser($user, 'company_traveller_deleted', 'Deleted a saved traveller: '.$traveller->fullName(), $traveller);

        $traveller->delete();
    }

    /** @param array<string, mixed> $data the form's fields */
    private function attributes(array $data): array
    {
        $number = $data['passport_no'] ?? null;
        $passport = blank($number) ? null : [
            'passport_no' => strtoupper(trim((string) $number)),
            'passport_issue_country' => $data['passport_issue_country'] ?? null,
            'passport_issue_date' => $data['passport_issue_date'] ?? null,
            'passport_exp' => $data['passport_exp'] ?? null,
        ];

        return [
            'title' => $data['title'],
            'first_name' => $data['first_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'last_name' => $data['last_name'],
            'gender' => $data['gender'],
            'dob' => $data['dob'],
            'nationality' => $data['nationality'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'frequent_flyer_number' => $data['frequent_flyer_number'] ?? null,
            'passport' => $passport,
            'passport_last4' => $passport ? substr($passport['passport_no'], -4) : null,
            'passport_expires_on' => $passport['passport_exp'] ?? null,
        ];
    }

    private function fromPassenger(array $passenger): array
    {
        return Arr::only($passenger, [
            'title', 'first_name', 'middle_name', 'last_name', 'gender', 'dob', 'nationality', 'frequent_flyer_number',
            ...self::PASSPORT_FIELDS,
        ]);
    }

    /** The date of birth is encrypted, so it's compared after narrowing by name. */
    private function findSamePerson(Company $company, array $passenger): ?CompanyTraveller
    {
        return $company->travellers()
            ->where('first_name', $passenger['first_name'] ?? '')
            ->where('last_name', $passenger['last_name'] ?? '')
            ->get()
            ->first(fn (CompanyTraveller $traveller): bool => $traveller->dob === ($passenger['dob'] ?? null));
    }
}
