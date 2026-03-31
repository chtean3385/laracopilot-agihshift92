<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HotelWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $hotelName;
    public string $adminName;
    public string $adminEmail;
    public string $adminPassword;
    public string $loginUrl;
    public string $plan;

    public function __construct(
        string $hotelName,
        string $adminName,
        string $adminEmail,
        string $adminPassword,
        string $plan = 'Basic'
    ) {
        $this->hotelName     = $hotelName;
        $this->adminName     = $adminName;
        $this->adminEmail    = $adminEmail;
        $this->adminPassword = $adminPassword;
        $this->loginUrl      = 'https://resort.dreamstechnology.in/login';
        $this->plan          = $plan;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Hotel CRM — Your Account is Ready 🎉',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hotel-welcome',
        );
    }
}
