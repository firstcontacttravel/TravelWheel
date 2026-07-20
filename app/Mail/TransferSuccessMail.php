<?php

namespace App\Mail;

use App\Models\Transfer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransferSuccessMail extends Mailable
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

        return $this->subject('Your Transfer Booking is Confirmed! 🚘')
                    ->view('emails.transfer_success')
                    ->with([
                        'name'             => $this->booking->full_name ?? 'Customer',
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
                    ]);
    }
}