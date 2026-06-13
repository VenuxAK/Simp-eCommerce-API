<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status Updated</title>
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
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            background-color: #dbeafe;
            color: #1d4ed8;
            margin: 0 0 24px;
        }
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
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
        .tracking {
            background-color: #f4f4f5;
            border-radius: 8px;
            padding: 16px;
            font-size: 14px;
            margin: 0 0 24px;
        }
        .tracking strong {
            display: block;
            font-size: 13px;
            color: #a1a1aa;
            margin-bottom: 4px;
        }
        .tracking a {
            color: #18181b;
            font-weight: 600;
        }
        .hr {
            border: none;
            border-top: 1px solid #e4e4e7;
            margin: 24px 0;
        }
        .meta {
            font-size: 13px;
            color: #a1a1aa;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <div class="logo">{{ config('app.name') }}</div>

            @switch($newStatus)
                @case('shipped')
                    <h1>Your Order Has Been Shipped</h1>
                    <div class="badge badge-success">Shipped</div>
                    <p>
                        Great news! Your order <strong>{{ $order->order_number }}</strong>
                        has been shipped and is on its way to you.
                    </p>

                    @if ($order->shipment?->tracking_number)
                        <div class="tracking">
                            <strong>Tracking Number</strong>
                            @if ($order->shipment->tracking_url)
                                <a href="{{ $order->shipment->tracking_url }}" target="_blank">
                                    {{ $order->shipment->tracking_number }}
                                </a>
                            @else
                                {{ $order->shipment->tracking_number }}
                            @endif
                        </div>
                    @endif
                    @break

                @case('delivered')
                    <h1>Your Order Has Been Delivered</h1>
                    <div class="badge badge-success">Delivered</div>
                    <p>
                        Your order <strong>{{ $order->order_number }}</strong>
                        has been delivered. We hope you enjoy your purchase!
                    </p>
                    @break

                @case('cancelled')
                    <h1>Your Order Has Been Cancelled</h1>
                    <div class="badge badge-warning">Cancelled</div>
                    <p>
                        Your order <strong>{{ $order->order_number }}</strong>
                        has been cancelled. If you did not request this cancellation,
                        please contact our support team.
                    </p>
                    @break

                @default
                    <h1>Order Status Updated</h1>
                    <div class="badge">{{ ucfirst($newStatus) }}</div>
                    <p>
                        Your order <strong>{{ $order->order_number }}</strong>
                        status has been updated to <strong>{{ $newStatus }}</strong>.
                    </p>
            @endswitch

            <hr class="hr">
            <p class="meta">
                Order placed on: {{ $order->created_at?->format('M d, Y g:i A') ?? 'N/A' }}<br>
                Order total: {{ number_format($order->total_amount, 0) }} Ks
            </p>
        </div>
    </div>
</body>
</html>
