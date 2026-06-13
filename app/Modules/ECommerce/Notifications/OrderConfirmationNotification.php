<?php

namespace App\Modules\ECommerce\Notifications;

use App\Modules\ECommerce\Mail\OrderConfirmationMail;
use App\Modules\Sales\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification dispatched when a customer successfully places an
 * online COD order.
 *
 * Responsibilities (SRP):
 *   - Decides which channels to deliver on (mail only for now).
 *   - Builds the mail representation via OrderConfirmationMail.
 *
 * @see OrderConfirmationMail
 */
class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Order  $order  Freshly placed online order.
     *                         Must be commited to DB before this
     *                         notification is dispatched.
     */
    public function __construct(
        private readonly Order $order,
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
     * Build the mail containing the order summary.
     *
     * @return OrderConfirmationMail
     */
    public function toMail(object $notifiable): OrderConfirmationMail
    {
        $mail = new OrderConfirmationMail($this->order);

        return $mail->to($notifiable->getEmailForPasswordReset());
    }
}
