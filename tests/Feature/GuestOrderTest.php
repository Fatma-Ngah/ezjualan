<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\OrderStatus;
use Livewire\Livewire;

test('guests can view the product catalogue', function () {
    $product = Product::factory()->create(['name' => 'Kurung Dahlia']);
    ProductVariant::factory()->for($product)->create(['stock' => 5]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Kurung Dahlia');
});

test('a guest can place a direct order without logging in', function () {
    $product = Product::factory()->create(['price' => 89]);
    $variant = ProductVariant::factory()->for($product)->create([
        'size' => 'M',
        'color' => 'Hitam',
        'stock' => 10,
    ]);

    Livewire::test('pages::store.product', ['product' => $product])
        ->set('selectedVariantId', $variant->id)
        ->set('quantity', 2)
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

    expect($order->status)->toBe(OrderStatus::PendingPayment)
        ->and((float) $order->shipping_fee)->toBe(8.0)
        ->and((float) $order->total)->toBe(186.0)
        ->and($variant->refresh()->stock)->toBe(10);
});

test('shipping details are required for a guest order', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 5]);

    Livewire::test('pages::store.product', ['product' => $product])
        ->set('selectedVariantId', $variant->id)
        ->call('placeOrder')
        ->assertHasErrors([
            'customerName',
            'email',
            'phone',
            'address',
            'postcode',
            'city',
            'state',
        ]);

    expect(Order::query()->count())->toBe(0);
});
