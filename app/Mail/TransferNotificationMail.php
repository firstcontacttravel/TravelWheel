<?php

namespace App\Mail;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(Transfer $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        $vehicleTypeMap = [
            'saloon'  => 'Saloon',
            'suv'     => 'SUV',
            'van'     => 'Mini Van',
            'bus'     => 'Bus',
            'luxury'  => 'Luxury',
        ];

        $vehicleTypeDisplay = $vehicleTypeMap[$this->booking->vehicle_type] ?? ucfirst($this->booking->vehicle_type);

        return $this->subject('New Transfer Booking — ' . ($this->booking->full_name ?? $this->booking->email))
                    ->view('emails.transfer_notification')
                    ->with([
                        'name'             => $this->booking->full_name,
                        'email'            => $this->booking->email,
                        'phone'            => $this->booking->phone_number,
                        'vehicle_type'     => $vehicleTypeDisplay,
                        'vehicle_name'     => $this->booking->vehicle_name,
                        'pickup_location'  => $this->booking->pickup_location,
                        'dropoff_location' => $this->booking->dropoff_location,
                        'pickup_date'      => $this->booking->pickup_date,
                        'pickup_time'      => $this->booking->pickup_time,
                        'passengers'       => $this->booking->passengers,
                        'distance_km'      => $this->booking->distance_km,
                        'flight_number'    => $this->booking->flight_number,
                        'special_requests' => $this->booking->special_requests,
                        'amount'           => $this->booking->amount,
                        'reference'        => $this->booking->payment_reference,
                        'payment_option'   => strtoupper($this->booking->payment_option),
                    ]);
    }
}