<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $customers = Customer::all();

        if ($products->isEmpty() || $customers->isEmpty()) {
            return;
        }

        for ($i = 0; $i < 25; $i++) {
            DB::transaction(function () use ($products, $customers) {
                $order = Order::factory()->create([
                    'customer_id' => $customers->random()->id,
                    'subtotal' => 0,
                    'tax_total' => 0,
                    'grand_total' => 0,
                ]);

                $selectedProducts = $products->random(
                    fake()->numberBetween(
                        1,
                        min(4, $products->count())
                    )
                );

                $subtotal = 0;
                $taxTotal = 0;

                foreach ($selectedProducts as $product) {
                    $quantity = fake()->numberBetween(1, 5);

                    $lineSubtotal = round(
                        (float) $product->price * $quantity,
                        2
                    );

                    $lineTax = round(
                        $lineSubtotal * (float) $product->tax_percent / 100,
                        2
                    );

                    OrderItem::factory()->create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->price,
                        'tax_percent' => $product->tax_percent,
                        'subtotal' => $lineSubtotal,
                        'tax' => $lineTax,
                        'total' => round(
                            $lineSubtotal + $lineTax,
                            2
                        ),
                    ]);

                    $subtotal += $lineSubtotal;
                    $taxTotal += $lineTax;
                }

                $subtotal = round($subtotal, 2);
                $taxTotal = round($taxTotal, 2);

                $order->update([
                    'subtotal' => $subtotal,
                    'tax_total' => $taxTotal,
                    'grand_total' => round(
                        $subtotal + $taxTotal,
                        2
                    ),
                ]);
            });
        }
    }
}