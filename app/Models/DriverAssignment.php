<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'driver_id',
        'car_hire_id',
        'transfer_id',
        'booking_type',
        'car_model',
        'car_colour',
        'plate_number',
        'car_images',
        'notes',
        'email_sent_at',
        'assigned_at',
    ];

    protected $casts = [
        'car_images' => 'array',
        'assigned_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function carHire()
    {
        return $this->belongsTo(CarHire::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function booking()
    {
        return $this->booking_type === 'car_hire'
            ? $this->carHire
            : $this->transfer;
    }

    public function carImageUrls(): array
    {
        if (empty($this->car_images)) {
            return [];
        }

        return array_map(fn ($path) => asset('assets/' . $path), $this->car_images);
    }

    public function emailSent(): bool
    {
        return $this->email_sent_at !== null;
    }
}
