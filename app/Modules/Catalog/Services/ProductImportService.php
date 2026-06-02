<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Supplier\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Business logic for ProductImport operations.
 */
class ProductImportService
{
    /**
     * Import products and variants from a CSV file.
     *
     * Creates or updates categories, suppliers, products, and variants
     * within a single database transaction. Returns a count of created
     * records and any row-level validation errors.
     */
    public function importFromFile(UploadedFile $file): array
    {
        $handle = fopen($file->getPathname(), 'r');
        $header = fgetcsv($handle);
        $created = 0;
        $errors = [];

        DB::transaction(function () use ($handle, $header, &$created, &$errors) {
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($header, $row);

                $rowValidator = Validator::make($data, [
                    'name' => ['required', 'string', 'max:255'],
                    'sku' => ['required', 'string', 'max:100'],
                    'base_price' => ['nullable', 'numeric', 'min:0'],
                    'price_adjustment' => ['nullable', 'numeric', 'min:0'],
                    'stock' => ['nullable', 'integer', 'min:0'],
                    'purchase_price' => ['nullable', 'numeric', 'min:0'],
                ]);

                if ($rowValidator->fails()) {
                    $errors[] = "Row " . ($created + 1) . ": " . implode('; ', $rowValidator->errors()->all());
                    continue;
                }

                $storeId = app()->bound('current_store') && ($store = app('current_store'))
                    ? $store->id
                    : (request()->header('X-Store')
                        ? \App\Modules\Store\Models\Store::where('slug', request()->header('X-Store'))->first()?->id
                        : 1);

                $category = Category::firstOrCreate(
                    ['name' => $data['category'] ?? 'Uncategorized'],
                    ['slug' => Str::slug($data['category'] ?? 'Uncategorized'), 'store_id' => $storeId],
                );
                $supplier = null;
                if (!empty($data['supplier'])) {
                    $supplier = Supplier::firstOrCreate(
                        ['name' => $data['supplier']],
                        ['store_id' => $storeId],
                    );
                }

                try {
                    $product = Product::firstOrCreate(
                        ['name' => $data['name']],
                        [
                            'category_id' => $category->id,
                            'supplier_id' => $supplier?->id,
                            'store_id' => $storeId,
                            'slug' => Str::slug($data['name']) . '-' . Str::random(8),
                            'base_price' => $data['base_price'] ?? 0,
                        ],
                    );

                    $product->variants()->updateOrCreate(
                        ['sku' => $data['sku']],
                        [
                            'size' => $data['size'] ?? null,
                            'color' => $data['color'] ?? null,
                            'price_adjustment' => $data['price_adjustment'] ?? 0,
                            'purchase_price' => $data['purchase_price'] ?? null,
                            'stock_quantity' => $data['stock'] ?? 0,
                        ],
                    );
                    $created++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$created}: {$e->getMessage()}";
                }
            }
        });

        fclose($handle);
        return ['created' => $created, 'errors' => $errors];
    }
}
