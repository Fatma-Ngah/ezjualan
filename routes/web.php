<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::store.catalogue')->name('home');
Route::livewire('produk/{product}', 'pages::store.product')->name('products.show');
Route::livewire('tempahan/{order}', 'pages::store.order-confirmation')->name('orders.confirmation');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('admin/produk', 'pages::admin.products')->name('admin.products');
    Route::livewire('admin/produk/{product}/sunting', 'pages::admin.products')->name('admin.products.edit');
    Route::livewire('admin/tempahan', 'pages::admin.orders')->name('admin.orders');
    Route::livewire('admin/kupon', 'pages::admin.coupons')->name('admin.coupons');
});

require __DIR__.'/settings.php';
