<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
class ProductController extends Controller
{
    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'threshold' => ['sometimes', 'integer', 'min:1'],
        ]);

        $threshold = (int) ($validated['threshold']
            ?? config('inventory.low_stock_threshold', 5));

        $products = Product::where('stock', '<', $threshold)
            ->orderBy('stock')
            ->get();

        return response()->json([
            'message' => 'Low-stock products retrieved successfully.',
            'data' => [
                'threshold' => $threshold,
                'count' => $products->count(),
                'products' => $products,
            ],
        ]);
    }
}
