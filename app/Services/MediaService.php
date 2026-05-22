<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class MediaService
{
    public function uploadImage(Model $model, UploadedFile $file, string $field = 'image', string $disk = 'public'): string
    {
        $directory = class_basename($model::class) === 'Product' ? 'products' : 'variants';

        if ($model->$field) {
            Storage::disk($disk)->delete($model->$field);
        }

        $path = $file->store($directory, $disk);
        $model->update([$field => $path]);

        return $path;
    }
}
