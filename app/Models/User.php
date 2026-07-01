<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'visa_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return false;
        }

        if ($this->is_admin || in_array($this->visa_role, ['administrator', 'visa_officer', 'finance', 'support'], true)) {
            return true;
        }

        $adminEmails = collect(explode(',', (string) env('ADMIN_EMAILS', '')))
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter();

        return $adminEmails->contains(strtolower($this->email));
    }

    public function isVisaAdministrator(): bool
    {
        if ($this->is_admin || $this->visa_role === 'administrator') {
            return true;
        }

        return collect(explode(',', (string) env('ADMIN_EMAILS', '')))->map(fn (string $email) => strtolower(trim($email)))->contains(strtolower($this->email));
    }

    public function canOperateVisas(): bool
    {
        return $this->isVisaAdministrator() || $this->visa_role === 'visa_officer';
    }

    public function canViewVisaOperations(): bool
    {
        return $this->canOperateVisas() || $this->visa_role === 'support';
    }
}
