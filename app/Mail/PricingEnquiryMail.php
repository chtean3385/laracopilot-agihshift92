<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PricingEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $enquiryName;
    public string $hotelName;
    public string $phone;
    public string $planLabel;
    public int|float $planPrice;
    public string $rooms;
    public string $enquiryMessage;
    public string $submittedAt;

    public function __construct(array $data)
    {
        $this->enquiryName = $data['name'];
        $this->hotelName   = $data['hotel'];
        $this->phone       = $data['phone'];
        $this->planLabel   = $data['plan_label'];
        $this->planPrice   = $data['plan_price'];
        $this->rooms       = $data['rooms'] ?? '';
        $this->enquiryMessage = $data['message'] ?? '';
        $this->submittedAt = now()->format('d M Y, h:i A');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🏨 New Pricing Enquiry — ' . $this->planLabel . ' Plan | ' . $this->hotelName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pricing-enquiry',
        );
    }
}
