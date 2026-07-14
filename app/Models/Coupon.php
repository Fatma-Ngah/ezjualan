<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code
 * @property string $percentage
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $expires_at
 * @property bool $is_active
 */
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'code',
        'percentage',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /** @return Attribute<string, string> */
    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value): string => Str::upper(Str::squish($value)),
        );
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function validationMessage(): ?string
    {
        if (! $this->is_active) {
            return 'Kupon ini tidak aktif.';
        }

        if ($this->starts_at?->isFuture()) {
            return 'Kupon ini belum boleh digunakan.';
        }

        if ($this->expires_at?->isPast()) {
            return 'Kupon ini telah tamat tempoh.';
        }

        return null;
    }

    public function discountInCents(int $subtotalInCents): int
    {
        return min(
            $subtotalInCents,
            (int) round($subtotalInCents * ((float) $this->percentage / 100)),
        );
    }
}
