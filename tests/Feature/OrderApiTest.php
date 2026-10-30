<?php
namespace Tests\Feature;
use App\Jobs\SendOrderConfirmation;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_created_and_stock_is_deducted(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 100.00,
            'tax_percent' => 10,
            'stock' => 5,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', '200.00')
            ->assertJsonPath('data.tax_total', '20.00')
            ->assertJsonPath('data.grand_total', '220.00');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 3,
        ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $customer->id,
            'subtotal' => 200.00,
            'tax_total' => 20.00,
            'grand_total' => 220.00,
        ]);

        Queue::assertPushed(
            SendOrderConfirmation::class,
            fn ($job) => $job->orderId > 0
        );
    }

    public function test_order_is_rejected_when_stock_is_insufficient(): void
    {
        Queue::fake();

        $customer = Customer::factory()->create();
        $product = Product::factory()->create([
            'price' => 100.00,
            'tax_percent' => 10,
            'stock' => 1,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer' => [
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $response->assertUnprocessable();

        $this->assertDatabaseCount('orders', 0);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 1,
        ]);

        Queue::assertNothingPushed();
    }
}
