<?php

namespace App\Modules\Identity\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Renders the password-reset email sent to staff users.
 *
 * Pure rendering concern — only builds the email subject, view, and
 * data payload. Instantiated by StaffPasswordResetNotification::toMail().
 *
 * The reset URL points to the dashboard SPA, which collects the new
 * password and POSTs back to POST /auth/reset-password.
 *
 * @see \App\Modules\Identity\Notifications\StaffPasswordResetNotification
 */
class StaffPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $token        The password reset token.
     * @param  string  $email        The staff email (for the URL query).
     * @param  string  $frontendUrl  Base URL of the dashboard SPA.
     */
    public function __construct(
        public string $token,
        public string $email,
        public string $frontendUrl,
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
            $this->frontendUrl,
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
