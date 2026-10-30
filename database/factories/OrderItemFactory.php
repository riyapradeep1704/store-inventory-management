<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 1000);
        $taxPercent = fake()->randomElement([0, 5, 12, 18, 28]);
        $quantity = fake()->numberBetween(1, 5);

        $subtotal = round($price * $quantity, 2);
        $tax = round($subtotal * $taxPercent / 100, 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $price,
            'tax_percent' => $taxPercent,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
        ];
    }
}