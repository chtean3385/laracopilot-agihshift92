<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HotelFullAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $hotelName;
    public int    $totalRooms;
    public int    $occupiedRooms;
    public string $date;
    public string $dashboardUrl;

    public function __construct(
        string $hotelName,
        int    $totalRooms,
        int    $occupiedRooms,
        string $date
    ) {
        $this->hotelName     = $hotelName;
        $this->totalRooms    = $totalRooms;
        $this->occupiedRooms = $occupiedRooms;
        $this->date          = $date;
        $this->dashboardUrl  = 'https://resort.dreamstechnology.in/dashboard';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏨 Hotel Full Alert — ' . $this->hotelName . ' is fully booked today!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hotel-full-alert',
        );
    }
}
