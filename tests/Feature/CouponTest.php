<?php

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot open coupon management', function () {
    $this->get(route('admin.coupons'))->assertRedirect(route('login'));
});

test('an admin can create update and delete a coupon', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::admin.coupons')
        ->set('code', ' jimat-10 ')
        ->set('percentage', '10.50')
        ->set('startsAt', '2026-07-01T08:00')
        ->set('expiresAt', '2026-07-31T23:59')
        ->call('saveCoupon')
        ->assertHasNoErrors();

    $coupon = Coupon::query()->sole();

    expect($coupon->code)->toBe('JIMAT-10')
        ->and($coupon->percentage)->toBe('10.50')
        ->and($coupon->is_active)->toBeTrue();

    Livewire::actingAs($user)
        ->test('pages::admin.coupons')
        ->call('editCoupon', $coupon->id)
        ->set('percentage', '15')
        ->set('isActive', false)
        ->call('saveCoupon')
        ->assertHasNoErrors();

    expect($coupon->refresh()->percentage)->toBe('15.00')
        ->and($coupon->is_active)->toBeFalse();

    Livewire::actingAs($user)
        ->test('pages::admin.coupons')
        ->call('deleteCoupon', $coupon->id);

    $this->assertModelMissing($coupon);
});

test('coupon management validates percentage code uniqueness and dates', function () {
    $existingCoupon = Coupon::factory()->create(['code' => 'JIMAT10']);

    Livewire::actingAs(User::factory()->create())
        ->test('pages::admin.coupons')
        ->set('code', mb_strtolower($existingCoupon->code))
        ->set('percentage', '100.01')
        ->set('startsAt', '2026-08-02T12:00')
        ->set('expiresAt', '2026-08-01T12:00')
        ->call('saveCoupon')
        ->assertHasErrors(['code', 'percentage', 'expiresAt']);
});

test('coupon scheduling dates may be left empty independently', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::admin.coupons')
        ->set('code', 'TAMATSAHAJA')
        ->set('percentage', '5')
        ->set('expiresAt', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('saveCoupon')
        ->assertHasNoErrors();

    expect(Coupon::query()->sole()->starts_at)->toBeNull();
});

test('a guest can apply a case insensitive percentage coupon', function () {
    $product = Product::factory()->create(['price' => 100]);
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 10]);
    $coupon = Coupon::factory()->create(['code' => 'JIMAT15', 'percentage' => 15]);

    Livewire::test('pages::store.product', ['product' => $product])
        ->set('selectedVariantId', $variant->id)
        ->set('quantity', 2)
        ->set('couponCode', 'jimat15')
        ->call('applyCoupon')
        ->assertHasNoErrors('couponCode')
        ->assertSet('couponCode', 'JIMAT15')
        ->set('customerName', 'Nur Aisyah')
        ->set('email', 'aisyah@example.com')
        ->set('phone', '0123456789')
        ->set('address', '12, Jalan Melur')
        ->set('postcode', '70300')
        ->set('city', 'Seremban')
        ->set('state', 'Selangor')
        ->call('placeOrder')
        ->assertHasNoErrors()
        ->assertRedirect();

    $order = Order::query()->sole();

    expect($order->coupon_code)->toBe('JIMAT15')
        ->and((float) $order->discount_amount)->toBe(30.0)
        ->and((float) $order->total)->toBe(178.0);

    $this->get(route('orders.confirmation', $order))
        ->assertSuccessful()
        ->assertSee('JIMAT15');

    $this->actingAs(User::factory()->create())
        ->get(route('admin.orders'))
        ->assertSuccessful()
        ->assertSee('JIMAT15');

    $coupon->delete();

    expect($order->refresh()->coupon_id)->toBeNull()
        ->and($order->coupon_code)->toBe('JIMAT15')
        ->and((float) $order->discount_amount)->toBe(30.0);
});

test('checkout rejects coupons that are unavailable', function (array $attributes, string $message) {
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['stock' => 5]);
    $coupon = Coupon::factory()->create($attributes);

    Livewire::test('pages::store.product', ['product' => $product])
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasErrors('couponCode')
        ->assertSee($message);
})->with([
    'inactive' => [['is_active' => false], 'Kupon ini tidak aktif.'],
    'not started' => [['starts_at' => now()->addHour()], 'Kupon ini belum boleh digunakan.'],
    'expired' => [['expires_at' => now()->subHour()], 'Kupon ini telah tamat tempoh.'],
]);

test('coupon is validated again when the order is created', function () {
    $product = Product::factory()->create(['price' => 50]);
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 5]);
    $coupon = Coupon::factory()->create(['code' => 'SEKARANG10']);
    $component = Livewire::test('pages::store.product', ['product' => $product])
        ->set('couponCode', $coupon->code)
        ->call('applyCoupon')
        ->assertHasNoErrors('couponCode');

    $coupon->update(['is_active' => false]);

    $component
        ->set('selectedVariantId', $variant->id)
        ->set('customerName', 'Nur Aisyah')
        ->set('email', 'aisyah@example.com')
        ->set('phone', '0123456789')
        ->set('address', '12, Jalan Melur')
        ->set('postcode', '70300')
        ->set('city', 'Seremban')
        ->set('state', 'Selangor')
        ->call('placeOrder')
        ->assertHasErrors('couponCode');
});

test('discount is capped at the product subtotal', function () {
    $product = Product::factory()->create(['price' => 0.01]);
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 1]);
    $coupon = Coupon::factory()->create(['percentage' => 100]);

    $order = app(CreateOrder::class)->handle($variant, [
        'quantity' => 1,
        'customer_name' => 'Nur Aisyah',
        'email' => 'aisyah@example.com',
        'phone' => '0123456789',
        'address' => '12, Jalan Melur',
        'postcode' => '70300',
        'city' => 'Seremban',
        'state' => 'Selangor',
        'coupon_code' => $coupon->code,
    ]);

    expect((float) $order->discount_amount)->toBe(0.01)
        ->and((float) $order->total)->toBe(8.0);
});
