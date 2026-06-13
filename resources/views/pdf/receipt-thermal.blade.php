<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt</title>
    <style>
        @page { size: 58mm 200mm auto; margin: 0; }
        body { font-family: 'Courier New', monospace; font-size: 10px; width: 58mm; padding: 4px 6px; margin: 0; }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; }
        .qty { text-align: center; width: 20px; }
        .price { text-align: right; width: 55px; }
        .total-row td { font-weight: bold; padding-top: 4px; border-top: 1px solid #000; }
        .shop-name { font-size: 14px; font-weight: bold; margin-bottom: 2px; }
        .info { font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <div class="center shop-name">{{ $shop_name }}</div>
    <div class="center info">{{ $shop_address }}</div>
    <div class="center info">{{ $shop_phone }}</div>
    <div class="center info">Invoice: {{ $invoice->invoice_number }}</div>
    <div class="center info">{{ now()->format('Y-m-d H:i') }}</div>
    @if($invoice->order->customer)
        <div class="center info">Customer: {{ $invoice->order->customer->name }}</div>
    @endif
    <div class="divider"></div>
    <table>
        @foreach($invoice->order->items as $item)
        <tr>
            <td colspan="3">{{ Str::limit($item->variant->product->name, 22) }}</td>
        </tr>
        <tr>
            <td class="qty">{{ $item->quantity }}x</td>
            <td>{{ $item->variant->size }}/{{ $item->variant->color }}</td>
            <td class="price">{{ number_format($item->subtotal, 0) }}</td>
        </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2">TOTAL</td>
            <td class="price">{{ \App\Modules\Core\Helpers\CurrencyFormatter::format($invoice->order->total_amount) }}</td>
        </tr>
    </table>
    <div class="divider"></div>
    <div class="center">Thank you!</div>
</body>
</html>
