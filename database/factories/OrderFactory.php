<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\ProductVariant;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 29, 199);
        $shippingFee = fake()->randomElement([7, 8, 9, 10, 11, 18, 20]);

        return [
            'public_id' => (string) Str::uuid(),
            'order_number' => 'EJ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => fake()->words(3, true),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'color' => fake()->safeColorName(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'shipping_fee' => $shippingFee,
            'coupon_id' => null,
            'coupon_code' => null,
            'discount_amount' => 0,
            'total' => ($quantity * $unitPrice) + $shippingFee,
            'customer_name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('01########'),
            'address' => fake()->streetAddress(),
            'postcode' => fake()->postcode(),
            'city' => fake()->city(),
            'state' => 'Negeri Sembilan',
            'shipping_note' => null,
            'status' => OrderStatus::PendingPayment,
            'stock_deducted_at' => null,
        ];
    }
}
