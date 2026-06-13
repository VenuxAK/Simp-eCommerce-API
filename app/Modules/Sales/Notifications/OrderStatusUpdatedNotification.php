<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\Sales\Mail\OrderStatusUpdatedMail;
use App\Modules\Sales\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notification dispatched when an online order's status changes.
 *
 * Responsibilities (SRP):
 *   - Decides which channels to deliver on (mail only for now).
 *   - Builds the mail representation via OrderStatusUpdatedMail.
 */
class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Order   $order      The order with updated status.
     * @param  string  $oldStatus  Previous status before the transition.
     * @param  string  $newStatus  New status after the transition.
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): OrderStatusUpdatedMail
    {
        $mail = new OrderStatusUpdatedMail($this->order, $this->oldStatus, $this->newStatus);

        return $mail->to($notifiable->getEmailForPasswordReset());
    }
}
