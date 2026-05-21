<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $suppliers = Supplier::withCount('products')->orderBy('name')->paginate(20);
        return SupplierResource::collection($suppliers);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier = Supplier::create($data);
        return new SupplierResource($supplier)->response()->setStatusCode(201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        return new SupplierResource($supplier->loadCount('products'));
    }

    public function update(Request $request, Supplier $supplier): SupplierResource
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $supplier->update($data);
        return new SupplierResource($supplier);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {
        $productCount = $supplier->products()->count();
        if ($productCount > 0) {
            return response()->json([
                'message' => "Cannot delete supplier with {$productCount} product(s).",
            ], 422);
        }
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted.']);
    }
}
