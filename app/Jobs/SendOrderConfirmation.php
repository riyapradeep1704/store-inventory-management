<?php
namespace App\Jobs;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderConfirmation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $order = Order::with('customer')->find($this->orderId);

        if (! $order) {
            Log::warning('Order confirmation skipped: order not found.', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        // Simulate sending an email; no real email is sent.
        Log::info('Order confirmation email simulated.', [
            'order_id' => $order->id,
            'customer_email' => $order->customer->email,
            'grand_total' => $order->grand_total,
        ]);
    }
}
