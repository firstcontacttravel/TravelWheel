<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'department_id',
        'deactivated_at',
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
            'deactivated_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The CEO. The only account that manages staff, departments and settings.
     */
    public function isAdmin(): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return collect(explode(',', (string) env('ADMIN_EMAILS', '')))
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter()
            ->contains(strtolower((string) $this->email));
    }

    public function isDeactivated(): bool
    {
        return $this->deactivated_at !== null;
    }

    public function inDepartment(string ...$slugs): bool
    {
        return $this->department_id !== null && in_array($this->department?->slug, $slugs, true);
    }

    /**
     * Marking payments paid, refunds and voids, TravelFlex credit decisions.
     * visa_role 'finance' is the pre-department way of saying the same thing.
     */
    public function canHandleMoney(): bool
    {
        return $this->isAdmin() || $this->inDepartment(Department::FINANCE) || $this->visa_role === 'finance';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin' || $this->isDeactivated()) {
            return false;
        }

        return $this->isAdmin()
            || $this->department_id !== null
            || in_array($this->visa_role, ['administrator', 'visa_officer', 'finance', 'support'], true);
    }

    public function isVisaAdministrator(): bool
    {
        return $this->isAdmin() || $this->visa_role === 'administrator';
    }

    public function canOperateVisas(): bool
    {
        return $this->isVisaAdministrator() || $this->inDepartment(Department::VISAS) || $this->visa_role === 'visa_officer';
    }

    /**
     * Every member of staff can see every queue; acting on it is gated
     * separately.
     */
    public function canViewVisaOperations(): bool
    {
        return $this->canOperateVisas() || $this->visa_role === 'support' || $this->department_id !== null;
    }
}
