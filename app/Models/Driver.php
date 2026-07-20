<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'photo',
        'license_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function photoUrl(): string
    {
        return $this->photo
            ? asset('storage/' . $this->photo)
            : asset('assets/img/drivers/default.png');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
