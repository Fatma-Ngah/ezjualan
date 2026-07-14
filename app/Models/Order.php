<?php

namespace App\Models;

use App\OrderStatus;
use Carbon\CarbonImmutable;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property string $order_number
 * @property int|null $product_variant_id
 * @property string $product_name
 * @property string $size
 * @property string $color
 * @property int $quantity
 * @property string $unit_price
 * @property string $shipping_fee
 * @property int|null $coupon_id
 * @property string|null $coupon_code
 * @property string $discount_amount
 * @property string $total
 * @property string $customer_name
 * @property string $email
 * @property string $phone
 * @property string $address
 * @property string $postcode
 * @property string $city
 * @property string $state
 * @property string|null $shipping_note
 * @property OrderStatus $status
 * @property CarbonImmutable|null $stock_deducted_at
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending_payment',
    ];

    protected $fillable = [
        'public_id',
        'order_number',
        'product_variant_id',
        'product_name',
        'size',
        'color',
        'quantity',
        'unit_price',
        'shipping_fee',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'total',
        'customer_name',
        'email',
        'phone',
        'address',
        'postcode',
        'city',
        'state',
        'shipping_note',
        'status',
        'stock_deducted_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'stock_deducted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ProductVariant, $this> */
    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /** @return BelongsTo<Coupon, $this> */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
