<?php

namespace App\Modules\Catalog\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Handles image uploads for catalog entities (products and variants).
 *
 * Resolves the storage directory from the model class name so that
 * product and variant images land in separate folders automatically.
 * Deletes the old image before replacing it to avoid orphaned files.
 */
class MediaService
{
    /**
     * Upload an image for a product or variant and replace any existing one.
     *
     * The storage subdirectory is derived from the model class name so that
     * product and variant images are neatly separated without configuration.
     * Old images are cleaned up synchronously to prevent storage bloat.
     */
    public function uploadImage(Model $model, UploadedFile $file, string $field = 'image', string $disk = 'public'): string
    {
        $directory = 'variants';
        if (class_basename($model::class) === 'Product') $directory = 'products';
        elseif (class_basename($model::class) === 'Brand') $directory = 'brands';

        if ($model->$field) {
            Storage::disk($disk)->delete($model->$field);
        }

        $path = $file->store($directory, $disk);
        $model->update([$field => $path]);

        return $path;
    }
}
