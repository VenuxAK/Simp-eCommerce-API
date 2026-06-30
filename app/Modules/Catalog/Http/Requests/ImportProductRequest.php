<?php

namespace App\Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates an uploaded CSV file before dispatching the import job.
 *
 * Accepts .csv and .txt MIME types because Excel commonly saves CSVs
 * with the plain-text MIME type when using "Save As CSV".
 * The 2 MB size cap (2048 KB) prevents runaway file uploads.
 */
class ImportProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermissionTo('products.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.mimes' => 'The file must be a CSV (.csv or .txt).',
            'file.max' => 'The file may not exceed 2 MB.',
        ];
    }
}
