<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;

class ProductExportService
{
    public function exportToCsv(): string
    {
        $products = Product::with(['category', 'variants', 'supplier'])->orderBy('name')->get();
        $csv = "category,name,sku,size,color,base_price,price_adjustment,purchase_price,stock,supplier\n";

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $csv .= implode(',', [
                    '"' . ($product->category?->name ?? '') . '"',
                    '"' . $product->name . '"',
                    '"' . $variant->sku . '"',
                    '"' . ($variant->size ?? '') . '"',
                    '"' . ($variant->color ?? '') . '"',
                    $product->base_price,
                    $variant->price_adjustment,
                    $variant->purchase_price ?? '',
                    $variant->stock_quantity,
                    '"' . ($product->supplier?->name ?? '') . '"',
                ]) . "\n";
            }
        }

        return $csv;
    }
}
