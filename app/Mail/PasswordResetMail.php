<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $resetUrl;
    public string $userName;
    public string $resortName;
    public string $tagline;

    public function __construct(User $user, string $token)
    {
        $this->userName  = $user->name;
        $this->resetUrl  = url('/reset-password/' . $token . '?email=' . urlencode($user->email));

        $settings = Setting::first();
        $this->resortName = $settings->resort_name ?? 'Resort CRM';
        $this->tagline    = $settings->tagline ?? '';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password — ' . $this->resortName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset',
        );
    }
}
