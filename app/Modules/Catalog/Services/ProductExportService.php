<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;

/**
 * Generates CSV exports of the product catalog flattened by variant.
 *
 * Each row represents one variant with its parent product's category,
 * name, base price, and supplier denormalised into the same line.
 */
class ProductExportService
{
    /**
     * Build a CSV string with one row per variant.
     *
     * Product-level data (category, name, base price, supplier) is
     * duplicated across each variant row so that the file can be
     * consumed directly by spreadsheet tools or re-imported.
     */
    public function exportToCsv(): string
    {
        $products = Product::with(['category', 'variants', 'supplier'])->orderBy('name')->get();
        $csv = "category,name,sku,size,color,base_price,price_adjustment,purchase_price,stock,supplier\n";

        foreach ($products as $product) {
            foreach ($product->variants as $variant) {
                $csv .= implode(',', [
                    '"'.($product->category?->name ?? '').'"',
                    '"'.$product->name.'"',
                    '"'.$variant->sku.'"',
                    '"'.($variant->size ?? '').'"',
                    '"'.($variant->color ?? '').'"',
                    $product->base_price,
                    $variant->price_adjustment,
                    $variant->purchase_price ?? '',
                    $variant->stock_quantity,
                    '"'.($product->supplier?->name ?? '').'"',
                ])."\n";
            }
        }

        return $csv;
    }
}
