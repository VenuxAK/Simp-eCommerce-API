<?php

namespace App\Modules\Customer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Renders the password-reset email sent to customers.
 *
 * This is a pure rendering concern — the Mailable only builds the
 * email subject, view, and data payload. It is instantiated by
 * CustomerPasswordResetNotification::toMail() and does NOT decide
 * when or how the notification is dispatched (that is the
 * Notification's job — SRP).
 *
 * The reset URL points to the storefront SPA, which collects the
 * new password and POSTs back to POST /customer/reset-password.
 *
 * @see \App\Modules\Customer\Notifications\CustomerPasswordResetNotification
 */
class CustomerPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $token           The password reset token.
     * @param  string  $email           The customer's email (for the URL query).
     * @param  string  $storefrontUrl   Base URL of the Nuxt storefront SPA.
     */
    public function __construct(
        public string $token,
        public string $email,
        public string $storefrontUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password — SimpCommerce',
        );
    }

    public function content(): Content
    {
        $resetUrl = sprintf(
            '%s/reset-password?token=%s&email=%s',
            $this->storefrontUrl,
            urlencode($this->token),
            urlencode($this->email),
        );

        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetUrl' => $resetUrl,
                'recipientEmail' => $this->email,
            ],
        );
    }
}
