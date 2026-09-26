<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function reserve(Request $request, Product $product)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $reservedProduct = DB::transaction(function () use ($product, $validated) {

            $product = Product::where('id', $product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->stock < $validated['quantity']) {
                return null;
            }

            $product->stock -= $validated['quantity'];
            $product->save();

            return $product;
        });

        if (!$reservedProduct) {
            return response()->json([
                'message' => 'Insufficient stock.',
            ], 409);
        }

        return response()->json([
            'message' => 'Product reserved successfully.',
            'product' => $reservedProduct,
        ], 200);
    }
}