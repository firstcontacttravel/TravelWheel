<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ShipmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shipment;
    public $filename;

    public function __construct($shipment, $filename)
    {
        $this->shipment = $shipment;
        $this->filename = $filename;
    }

    public function build()
    {
        $mail = $this->from('info@travelwheel.ng', 'Travel Wheel')
            ->subject('TravelWheel, Air Cargo Shipment')
            ->markdown('emails.shipment')
            ->cc(['holyjester@gmail.com', 'augustina@travelwheel.ng'])
            ->with(['shipment' => $this->shipment]);

        $pdfPath = Storage::path('public/shipments/' . $this->filename);
        if (file_exists($pdfPath)) {
            $mail->attach($pdfPath);
        }

        return $mail;
    }
}
