<?php

namespace App\Services;

use App\Mail\VisaPortalAccessCodeMail;
use App\Models\VisaApplication;
use App\Models\VisaPortalAccessCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class VisaPortalAccessService
{
    public function requestCode(VisaApplication $application, string $email, string $ip): void
    {
        $email = strtolower(trim($email));
        if (! $application->contact_email || ! hash_equals(strtolower($application->contact_email), $email)) {
            throw ValidationException::withMessages(['email' => 'The application reference and email address do not match.']);
        }

        $key = 'visa-portal-code:'.$application->id.':'.$ip;
        if (! RateLimiter::attempt($key, 5, fn () => true, 600)) {
            throw ValidationException::withMessages(['email' => 'Too many access-code requests. Please try again in 10 minutes.']);
        }

        VisaPortalAccessCode::query()->where('visa_application_id', $application->id)->whereNull('used_at')->update(['used_at' => now()]);
        $code = (string) random_int(100000, 999999);
        VisaPortalAccessCode::query()->create([
            'visa_application_id' => $application->id,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);
        Mail::to($email)->queue(new VisaPortalAccessCodeMail($application, $code));
    }

    public function verify(VisaApplication $application, string $code): bool
    {
        $challenge = VisaPortalAccessCode::query()->where('visa_application_id', $application->id)->whereNull('used_at')->latest()->first();
        if (! $challenge || $challenge->expires_at->isPast() || $challenge->attempts >= 5) {
            return false;
        }

        $challenge->increment('attempts');
        if (! Hash::check($code, $challenge->code_hash)) {
            return false;
        }

        $challenge->update(['used_at' => now()]);
        session()->put("visa_portal_access.{$application->reference}", now()->addHours(8)->timestamp);

        return true;
    }

    public function authorize(VisaApplication $application): bool
    {
        return (int) session("visa_portal_access.{$application->reference}") > now()->timestamp;
    }
}
