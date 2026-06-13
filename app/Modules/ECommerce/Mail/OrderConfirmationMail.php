<?php

namespace App\Modules\ECommerce\Mail;

use App\Modules\Sales\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Renders the order-confirmation email sent after a customer places an
 * online COD order via the storefront checkout flow.
 *
 * @see \App\Modules\ECommerce\Notifications\OrderConfirmationNotification
 */
class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Order  $order  Freshly created online order (source=online).
     *                         relations loaded: items.variant.product, shipment.address, invoice.
     */
    public function __construct(
        public Order $order,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation — ' . $this->order->order_number,
        );
    }

    public function content(): Content
    {
        $this->order->loadMissing(['items.variant.product', 'shipment.address', 'invoice']);

        $store = $this->order->store ?? app('current_store');

        return new Content(
            view: 'emails.order-confirmation',
            with: [
                'order' => $this->order,
                'store' => $store,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
