<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use App\Models\Customer;
use Illuminate\Http\Request;
use App\Jobs\SendOrderConfirmation;
class OrderController extends Controller
{
    public function store(StoreOrderRequest $request,OrderService $orderService): JsonResponse 
    {
        $order = $orderService->createOrder($request->validated());
        SendOrderConfirmation::dispatch($order->id);
        $order->load(['customer', 'items.product']);

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);
    }
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $customer = Customer::where('email', $validated['email'])->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found.',
            ], 404);
        }

        $orders = $customer->orders()
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Customer order history retrieved successfully.',
            'data' => [
                'customer' => $customer,
                'orders' => $orders,
            ],
        ]);
    }
}
