<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Labels</title>
    <style>
        @page { size: 62mm 40mm; margin: 2mm; }
        body { font-family: 'Courier New', monospace; font-size: 9px; margin: 0; padding: 0; }
        .label { width: 58mm; height: 36mm; display: flex; flex-direction: column; justify-content: center; text-align: center; border: 1px dashed #ccc; }
        .sku { font-size: 14px; font-weight: bold; letter-spacing: 1px; }
        .name { font-size: 10px; margin: 2px 0; }
        .detail { font-size: 9px; color: #555; }
        .price { font-size: 12px; font-weight: bold; margin-top: 4px; }
        .barcode { font-family: 'Libre Barcode 39', monospace; font-size: 22px; margin: 4px 0; letter-spacing: 2px; }
    </style>
</head>
<body>
    @foreach($variants as $variant)
    <div class="label">
        <div class="barcode">*{{ $variant->sku }}*</div>
        <div class="sku">{{ $variant->sku }}</div>
        <div class="name">{{ $variant->product->name }}</div>
        <div class="detail">{{ $variant->size }} / {{ $variant->color }}</div>
        <div class="price">{{ number_format($variant->product->base_price + $variant->price_adjustment, 0) }} Ks</div>
    </div>
    <div style="page-break-after: always;"></div>
    @endforeach
</body>
</html>
