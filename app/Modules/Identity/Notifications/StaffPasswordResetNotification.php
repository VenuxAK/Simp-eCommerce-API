<?php

namespace App\Modules\Identity\Notifications;

use App\Modules\Identity\Mail\StaffPasswordResetMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification dispatched when a staff member requests a password reset.
 *
 * Responsibilities (SRP):
 *   - Decides which channels to deliver on (mail only for now).
 *   - Builds the mail representation via StaffPasswordResetMail.
 *
 * The StaffPasswordResetMail handles the email rendering; this class only
 * decides that mail is the delivery channel — open for extension (add SMS
 * or Slack later) without changing the model or controller (OCP).
 *
 * @see StaffPasswordResetMail
 */
class StaffPasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  string  $token  Reset token (60-min expiry).
     */
    public function __construct(
        private readonly string $token,
    ) {}

    /**
     * Deliver via the mail channel.
     *
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail containing the reset link to the dashboard SPA.
     *
     * @return StaffPasswordResetMail
     */
    public function toMail(object $notifiable): StaffPasswordResetMail
    {
        $mail = new StaffPasswordResetMail(
            token: $this->token,
            email: $notifiable->getEmailForPasswordReset(),
            frontendUrl: config('app.frontend_url'),
        );

        return $mail->to($notifiable->getEmailForPasswordReset());
    }
}
