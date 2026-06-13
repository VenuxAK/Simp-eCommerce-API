<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; margin: 0; padding: 40px; }
        .header { text-align: center; border-bottom: 2px solid #1a1a1a; padding-bottom: 20px; margin-bottom: 20px; }
        .header h1 { font-size: 24px; margin: 0; }
        .header p { margin: 4px 0; color: #555; font-size: 11px; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .meta div { font-size: 11px; }
        .meta .label { color: #888; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f5f5f5; text-align: left; padding: 8px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12px; }
        .text-right { text-align: right; }
        .total { text-align: right; font-size: 18px; font-weight: bold; margin-top: 10px; }
        .footer { border-top: 1px solid #ddd; padding-top: 15px; margin-top: 30px; font-size: 10px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $shop_name }}</h1>
        <p>{{ $shop_address }}</p>
        <p>{{ $shop_phone }}</p>
    </div>

    <div class="meta">
        <div>
            <p><span class="label">Invoice:</span> {{ $invoice->invoice_number }}</p>
            <p><span class="label">Order:</span> {{ $invoice->order->order_number }}</p>
            <p><span class="label">Date:</span> {{ $invoice->issued_date }}</p>
            <p><span class="label">Due:</span> {{ $invoice->due_date ?? 'N/A' }}</p>
        </div>
        <div>
            @if($invoice->order->customer)
                <p><span class="label">Customer:</span> {{ $invoice->order->customer->name }}</p>
                <p><span class="label">Phone:</span> {{ $invoice->order->customer->phone ?? 'N/A' }}</p>
            @endif
            <p><span class="label">Status:</span> {{ ucfirst($invoice->status) }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Variant</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->order->items as $item)
            <tr>
                <td>{{ $item->variant->product->name }}</td>
                <td>{{ $item->variant->size }} / {{ $item->variant->color }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($item->unit_price) }}</td>
                <td class="text-right">{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total: {{ \App\Modules\Core\Helpers\CurrencyFormatter::format($invoice->order->total_amount) }}
    </div>

    @if($invoice->terms)
        <div class="footer">
            {{ $invoice->terms }}
        </div>
    @endif

    <div class="footer">
        Thank you for your purchase!
    </div>
</body>
</html>
