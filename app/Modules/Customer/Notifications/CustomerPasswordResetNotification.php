<?php

namespace App\Modules\Customer\Notifications;

use App\Modules\Customer\Mail\CustomerPasswordResetMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification dispatched when a customer requests a password reset.
 *
 * Responsibilities (SRP):
 *   - Decides which channels to deliver on (mail only for now).
 *   - Builds the mail representation via CustomerPasswordResetMail.
 *
 * Designed as a queued notification (ShouldQueue) so that password
 * reset emails don't block the API response. The queue worker must
 * be running for delivery; falls back to synchronous if queue is
 * unavailable.
 *
 * @see CustomerPasswordResetMail  Renders the actual email content.
 */
class CustomerPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $token  The password reset token (60-min expiry).
     *                         Stored in password_reset_tokens by PasswordBroker.
     */
    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * Deliver via the mail channel.
     *
     * Future channels (SMS, in-app) can be added here without touching
     * the model or the controller — open for extension, closed for
     * modification (OCP).
     *
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation containing the reset link.
     *
     * The reset URL points to the storefront SPA which handles the
     * token + email form submission back to the API.
     *
     * @return CustomerPasswordResetMail
     */
    public function toMail(object $notifiable): CustomerPasswordResetMail
    {
        $mail = new CustomerPasswordResetMail(
            token: $this->token,
            email: $notifiable->getEmailForPasswordReset(),
            storefrontUrl: config('app.storefront_url'),
        );

        return $mail->to($notifiable->getEmailForPasswordReset());
    }
}
