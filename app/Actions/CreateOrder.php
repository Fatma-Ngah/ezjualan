<?php

namespace App\Actions;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\ProductVariant;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    /** @param array{quantity: int, customer_name: string, email: string, phone: string, address: string, postcode: string, city: string, state: string, shipping_note?: string|null, coupon_code?: string|null} $data */
    public function handle(ProductVariant $variant, array $data): Order
    {
        return DB::transaction(function () use ($variant, $data): Order {
            $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $product = $lockedVariant->product()->firstOrFail();

            if (! $product->is_active || $lockedVariant->stock < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok untuk variasi yang dipilih tidak mencukupi.',
                ]);
            }

            $shippingFee = (float) config('shipping.rates.'.$data['state']);
            $subtotalInCents = (int) round(((float) $product->price) * 100) * $data['quantity'];
            $shippingFeeInCents = (int) round($shippingFee * 100);
            $coupon = $this->findCoupon($data['coupon_code'] ?? null);
            $discountInCents = $coupon?->discountInCents($subtotalInCents) ?? 0;

            return Order::query()->create([
                'public_id' => (string) Str::uuid(),
                'order_number' => 'EJ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'product_variant_id' => $lockedVariant->id,
                'product_name' => $product->name,
                'size' => $lockedVariant->size,
                'color' => $lockedVariant->color,
                'quantity' => $data['quantity'],
                'unit_price' => $product->price,
                'shipping_fee' => $shippingFeeInCents / 100,
                'coupon_id' => $coupon?->id,
                'coupon_code' => $coupon?->code,
                'discount_amount' => $discountInCents / 100,
                'total' => ($subtotalInCents - $discountInCents + $shippingFeeInCents) / 100,
                'customer_name' => $data['customer_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postcode' => $data['postcode'],
                'city' => $data['city'],
                'state' => $data['state'],
                'shipping_note' => $data['shipping_note'] ?? null,
                'status' => OrderStatus::PendingPayment,
            ]);
        }, attempts: 5);
    }

    private function findCoupon(?string $code): ?Coupon
    {
        if (blank($code)) {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', Str::upper(Str::squish($code)))
            ->lockForUpdate()
            ->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'couponCode' => 'Kod kupon tidak sah.',
            ]);
        }

        if ($message = $coupon->validationMessage()) {
            throw ValidationException::withMessages([
                'couponCode' => $message,
            ]);
        }

        return $coupon;
    }
}
