<?php

namespace App\Mail;

use App\Models\CarHire;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CarHireSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(CarHire $booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        $carTypeMap = [
            'saloon'  => 'Saloon',
            'suv'     => 'SUV',
            'van'     => 'Mini Van',
            'bus'     => 'Bus',
            'luxury'  => 'Luxury',
        ];

        $carTypeDisplay = $carTypeMap[$this->booking->car_type] ?? ucfirst($this->booking->car_type);

        return $this->subject('Your Car Hire Booking is Confirmed! 🚗')
                    ->view('emails.car_hire_success')
                    ->with([
                        'name'            => $this->booking->full_name ?? 'Customer',
                        'car_type'        => $carTypeDisplay,
                        'car_model'       => $this->booking->car_model,
                        'category'        => $this->booking->category,
                        'pickup_location' => $this->booking->pickup_location,
                        'dropoff_location'=> $this->booking->dropoff_location,
                        'pickup_date'     => $this->booking->pickup_date,
                        'pickup_time'     => $this->booking->pickup_time,
                        'passengers'      => $this->booking->passengers,
                        'amount'          => $this->booking->amount,
                        'reference'       => $this->booking->payment_reference,
                    ]);
    }
}