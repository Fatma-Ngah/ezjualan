<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    public function handle(Order $order, OrderStatus $newStatus): Order
    {
        return DB::transaction(function () use ($order, $newStatus): Order {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === $newStatus) {
                return $lockedOrder;
            }

            if (! in_array($newStatus, $lockedOrder->status->nextStatuses(), true)) {
                throw ValidationException::withMessages([
                    'selectedStatus' => 'Perubahan status ini tidak dibenarkan.',
                ]);
            }

            if ($newStatus === OrderStatus::Paid && $lockedOrder->stock_deducted_at === null) {
                $variant = ProductVariant::query()->lockForUpdate()->find($lockedOrder->product_variant_id);

                if ($variant === null || $variant->stock < $lockedOrder->quantity) {
                    throw ValidationException::withMessages([
                        'selectedStatus' => 'Stok tidak mencukupi untuk mengesahkan pembayaran.',
                    ]);
                }

                $variant->decrement('stock', $lockedOrder->quantity);
                $lockedOrder->stock_deducted_at = now();
            }

            if ($newStatus === OrderStatus::Cancelled && $lockedOrder->stock_deducted_at !== null) {
                ProductVariant::query()
                    ->whereKey($lockedOrder->product_variant_id)
                    ->increment('stock', $lockedOrder->quantity);

                $lockedOrder->stock_deducted_at = null;
            }

            $lockedOrder->status = $newStatus;
            $lockedOrder->save();

            return $lockedOrder->refresh();
        }, attempts: 5);
    }
}
