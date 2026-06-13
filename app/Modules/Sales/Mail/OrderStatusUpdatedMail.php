<?php

namespace App\Modules\Sales\Mail;

use App\Modules\Sales\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Renders the order-status-update email sent to customers when an
 * online order transitions to shipped or delivered.
 */
class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Order   $order      The order with updated status.
     * @param  string  $oldStatus  Previous status before the transition.
     * @param  string  $newStatus  New status after the transition.
     */
    public function __construct(
        public Order $order,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->newStatus) {
            'shipped' => 'Your Order Has Been Shipped — ' . $this->order->order_number,
            'delivered' => 'Your Order Has Been Delivered — ' . $this->order->order_number,
            'cancelled' => 'Your Order Has Been Cancelled — ' . $this->order->order_number,
            default => 'Order Status Updated — ' . $this->order->order_number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items.variant.product', 'shipment']);

        return new Content(
            view: 'emails.order-status-updated',
            with: [
                'order' => $this->order,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
            ],
        );
    }
}
