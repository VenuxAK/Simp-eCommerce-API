<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f4f4f5;
            color: #18181b;
        }
        .wrapper {
            max-width: 560px;
            margin: 40px auto;
            padding: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 40px 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 24px;
            color: #18181b;
        }
        h1 {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 4px;
        }
        .order-number {
            color: #52525b;
            font-size: 14px;
            margin: 0 0 24px;
        }
        p {
            font-size: 15px;
            line-height: 1.6;
            color: #52525b;
            margin: 0 0 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 24px;
        }
        th {
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 8px 8px 8px 0;
            border-bottom: 1px solid #e4e4e7;
        }
        td {
            padding: 12px 8px 12px 0;
            border-bottom: 1px solid #e4e4e7;
            font-size: 14px;
        }
        td:last-child, th:last-child {
            text-align: right;
            padding-right: 0;
        }
        .total-label {
            font-weight: 600;
            padding-top: 12px;
            border-bottom: none;
        }
        .total-value {
            font-weight: 700;
            font-size: 16px;
            padding-top: 12px;
            border-bottom: none;
        }
        .address {
            font-size: 14px;
            line-height: 1.5;
            color: #52525b;
            margin: 0 0 24px;
        }
        .address strong {
            color: #18181b;
        }
        .btn {
            display: inline-block;
            padding: 12px 28px;
            background-color: #18181b;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        .btn-wrap {
            text-align: center;
            margin: 0 0 24px;
        }
        .meta {
            font-size: 13px;
            color: #a1a1aa;
            line-height: 1.5;
        }
        .hr {
            border: none;
            border-top: 1px solid #e4e4e7;
            margin: 24px 0;
        }
        .notice {
            background-color: #fefce8;
            border: 1px solid #facc15;
            border-radius: 8px;
            padding: 16px;
            font-size: 14px;
            color: #a16207;
            margin: 0 0 24px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="logo">{{ $store?->name ?? config('app.name') }}</div>

            <h1>Thank You for Your Order</h1>
            <p class="order-number">Order #{{ $order->order_number }}</p>

            <p>Hi {{ $order->customer?->name ?? 'Valued Customer' }},</p>
            <p>
                We've received your order and it's now being processed.
                You'll receive a confirmation when your items have been shipped.
            </p>

            <div class="notice">
                <strong>Payment: Cash on Delivery</strong><br>
                Please prepare the exact amount when your order arrives.
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Qty</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                    @php $product = $item->variant?->product; @endphp
                    <tr>
                        <td>
                            {{ $product?->name ?? 'Product' }}
                            @if ($item->variant?->size || $item->variant?->color)
                                <br><small style="color:#a1a1aa">
                                    @if ($item->variant->size) Size: {{ $item->variant->size }} @endif
                                    @if ($item->variant->color) / Color: {{ $item->variant->color }} @endif
                                </small>
                            @endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($item->unit_price) }}</td>
                        <td>{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($item->subtotal) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="3" class="total-label">Total</td>
                        <td class="total-value">{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($order->total_amount) }}</td>
                    </tr>
                </tbody>
            </table>

            <h2 style="font-size:16px;font-weight:600;margin:0 0 8px;">Shipping Address</h2>
            @if ($order->shipment?->address)
            <p class="address">
                <strong>{{ $order->shipment->address->name }}</strong><br>
                {{ $order->shipment->address->street }}<br>
                {{ $order->shipment->address->city }}, {{ $order->shipment->address->state }}<br>
                {{ $order->shipment->address->postal_code }}<br>
                Phone: {{ $order->shipment->address->phone }}
            </p>
            @endif

            <p class="meta">
                Invoice: {{ $order->invoice?->invoice_number ?? 'N/A' }}<br>
                Ordered on: {{ $order->created_at?->format('M d, Y g:i A') ?? 'N/A' }}
            </p>

            <hr class="hr">

            <p style="text-align:center;margin:0;">
                <a href="{{ config('app.storefront_url') }}/orders/{{ $order->order_number }}"
                   style="color:#52525b;font-size:13px;">
                    View your order
                </a>
            </p>
        </div>
    </div>
</body>
</html>
