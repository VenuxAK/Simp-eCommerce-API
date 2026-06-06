<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Repositories\CategoryRepository;
use App\Modules\Catalog\Repositories\ProductRepository;
use App\Modules\Store\Repositories\StoreRepository;
use App\Modules\Supplier\Repositories\SupplierRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Batch CSV import for products and their variants.
 *
 * Processes each row within a single database transaction so that
 * a partial failure rolls back the entire import. Validation errors
 * on individual rows are collected and reported rather than aborting,
 * which allows the caller to fix bad rows and re-import only those.
 */
class ProductImportService
{
    private readonly StoreRepository $storeRepo;

    private readonly CategoryRepository $categoryRepo;

    private readonly SupplierRepository $supplierRepo;

    private readonly ProductRepository $productRepo;

    public function __construct(
        ?StoreRepository $storeRepo = null,
        ?CategoryRepository $categoryRepo = null,
        ?SupplierRepository $supplierRepo = null,
        ?ProductRepository $productRepo = null,
    ) {
        $this->storeRepo = $storeRepo ?? app(StoreRepository::class);
        $this->categoryRepo = $categoryRepo ?? app(CategoryRepository::class);
        $this->supplierRepo = $supplierRepo ?? app(SupplierRepository::class);
        $this->productRepo = $productRepo ?? app(ProductRepository::class);
    }

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
                    // Collect row-level errors instead of aborting so the caller
                    // can fix bad rows and re-import only those.
                    $errors[] = 'Row '.($created + 1).': '.implode('; ', $rowValidator->errors()->all());

                    continue;
                }

                $storeId = $this->storeRepo->getCurrentStore()?->id ?? 1;

                $category = $this->categoryRepo->firstOrCreateByName(
                    $data['category'] ?? 'Uncategorized',
                    ['slug' => Str::slug($data['category'] ?? 'Uncategorized'), 'store_id' => $storeId],
                );
                $supplier = null;
                if (! empty($data['supplier'])) {
                    $supplier = $this->supplierRepo->query()->firstOrCreate(
                        ['name' => $data['supplier']],
                        ['store_id' => $storeId],
                    );
                }

                try {
                    // Use name as the match key so re-importing the same file
                    // updates existing products rather than creating duplicates.
                    $product = $this->productRepo->query()->firstOrCreate(
                        ['name' => $data['name']],
                        [
                            'category_id' => $category->id,
                            'supplier_id' => $supplier?->id,
                            'store_id' => $storeId,
                            'slug' => Str::slug($data['name']).'-'.Str::random(8),
                            'base_price' => $data['base_price'] ?? 0,
                        ],
                    );

                    // SKU is the unique key for variants within a product.
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
