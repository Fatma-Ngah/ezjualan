<?php

use App\Actions\UpdateOrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Validation\ValidationException;

test('guests cannot open order management', function () {
    $this->get(route('admin.orders'))->assertRedirect(route('login'));
});

test('marking an order as paid deducts stock exactly once', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create(['stock' => 10]);
    $order = Order::factory()->for($variant, 'productVariant')->create([
        'quantity' => 3,
        'status' => OrderStatus::PendingPayment,
    ]);

    $action = app(UpdateOrderStatus::class);
    $action->handle($order, OrderStatus::Paid);
    $action->handle($order->refresh(), OrderStatus::Paid);

    expect($variant->refresh()->stock)->toBe(7)
        ->and($order->refresh()->stock_deducted_at)->not->toBeNull();
});

test('admin cannot confirm payment when stock is insufficient', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create(['stock' => 1]);
    $order = Order::factory()->for($variant, 'productVariant')->create([
        'quantity' => 2,
        'status' => OrderStatus::PendingPayment,
    ]);

    expect(fn () => app(UpdateOrderStatus::class)->handle($order, OrderStatus::Paid))
        ->toThrow(ValidationException::class);

    expect($variant->refresh()->stock)->toBe(1)
        ->and($order->refresh()->status)->toBe(OrderStatus::PendingPayment);
});

test('cancelling a paid order restores its deducted stock', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create(['stock' => 8]);
    $order = Order::factory()->for($variant, 'productVariant')->create([
        'quantity' => 2,
        'status' => OrderStatus::PendingPayment,
    ]);
    $action = app(UpdateOrderStatus::class);

    $action->handle($order, OrderStatus::Paid);
    $action->handle($order->refresh(), OrderStatus::Cancelled);

    expect($variant->refresh()->stock)->toBe(8)
        ->and($order->refresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($order->stock_deducted_at)->toBeNull();
});

test('authenticated admin can view order management', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.orders'))
        ->assertSuccessful();
});
