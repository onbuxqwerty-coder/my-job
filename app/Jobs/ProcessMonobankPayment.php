<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMonobankPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $statement) {}

    public function handle(): void
    {
        $statementId  = $this->statement['id']          ?? null;
        $description  = $this->statement['description'] ?? '';
        $amountKopeks = $this->statement['amount']      ?? 0;

        if ($amountKopeks <= 0) {
            return;
        }

        $amountUah = $amountKopeks / 100;

        if (! preg_match('/ORDER-(\d+)/i', $description, $matches)) {
            Log::warning('Monobank: order not found in description', ['description' => $description]);
            return;
        }

        $orderId = (int) $matches[1];

        if (Order::where('payment_id', $statementId)->exists()) {
            Log::info('Monobank: duplicate statement ignored', ['statement_id' => $statementId]);
            return;
        }

        $order = Order::where('id', $orderId)
                      ->where('status', 'pending')
                      ->first();

        if (! $order) {
            Log::warning('Monobank: pending order not found', ['order_id' => $orderId]);
            return;
        }

        if (abs($order->amount - $amountUah) > 0.01) {
            Log::warning('Monobank: amount mismatch', [
                'expected' => $order->amount,
                'received' => $amountUah,
                'order_id' => $orderId,
            ]);
            return;
        }

        $order->update([
            'status'     => 'paid',
            'payment_id' => $statementId,
            'paid_at'    => now(),
        ]);

        Log::info('Monobank: order paid', ['order_id' => $orderId, 'amount' => $amountUah]);

        // event(new \App\Events\OrderPaid($order));
    }
}
