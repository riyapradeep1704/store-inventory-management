<?php
namespace App\Services;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items']);

            $productIds = $items
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values();

            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'A requested product no longer exists.',
                    ]);
                }

                if ($product->stock < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => [
                            "Insufficient stock for {$product->name}. "
                            . "Available: {$product->stock}.",
                        ],
                    ]);
                }
            }

            $customer = Customer::firstOrCreate(
                ['email' => $data['customer']['email']],
                ['name' => $data['customer']['name']]
            );

            $order = $customer->orders()->create([
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
            ]);

            $subtotalCents = 0;
            $taxTotalCents = 0;

            foreach ($items as $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                $unitPriceCents = (int) round((float) $product->price * 100);
                $itemSubtotalCents = $unitPriceCents * $quantity;
                $itemTaxCents = (int) round(
                    $itemSubtotalCents * (float) $product->tax_percent / 100
                );
                $itemTotalCents = $itemSubtotalCents + $itemTaxCents;

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => number_format($unitPriceCents / 100, 2, '.', ''),
                    'tax_percent' => $product->tax_percent,
                    'subtotal' => number_format($itemSubtotalCents / 100, 2, '.', ''),
                    'tax' => number_format($itemTaxCents / 100, 2, '.', ''),
                    'total' => number_format($itemTotalCents / 100, 2, '.', ''),
                ]);

                $product->decrement('stock', $quantity);

                $subtotalCents += $itemSubtotalCents;
                $taxTotalCents += $itemTaxCents;
            }

            $order->update([
                'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                'tax_total' => number_format($taxTotalCents / 100, 2, '.', ''),
                'grand_total' => number_format(
                    ($subtotalCents + $taxTotalCents) / 100,
                    2,
                    '.',
                    ''
                ),
            ]);

            return $order;
        }, 3);
    }
}
