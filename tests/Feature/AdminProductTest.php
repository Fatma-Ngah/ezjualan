<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('guests cannot open product management', function () {
    $this->get(route('admin.products'))->assertRedirect(route('login'));
});

test('guests cannot open the product edit form', function () {
    $product = Product::factory()->create();

    $this->get(route('admin.products.edit', $product))->assertRedirect(route('login'));
});

test('an admin can create a product with separated variant stock', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::admin.products')
        ->set('name', 'Baju Kurung Melur')
        ->set('description', 'Baju kurung moden untuk kegunaan harian.')
        ->set('price', '79.90')
        ->set('variants', [
            ['id' => null, 'size' => 'M', 'color' => 'Hitam', 'sku' => 'BKM-BLK-M', 'stock' => 12],
            ['id' => null, 'size' => 'L', 'color' => 'Hitam', 'sku' => 'BKM-BLK-L', 'stock' => 7],
        ])
        ->call('saveProduct')
        ->assertHasNoErrors();

    $product = Product::query()->with('variants')->sole();

    expect($product->name)->toBe('Baju Kurung Melur')
        ->and($product->variants)->toHaveCount(2)
        ->and($product->variants->firstWhere('size', 'M')->stock)->toBe(12)
        ->and($product->variants->firstWhere('size', 'L')->stock)->toBe(7);
});

test('product variants cannot repeat the same size and colour', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::admin.products')
        ->set('name', 'Blouse Aira')
        ->set('description', 'Blouse yang selesa.')
        ->set('price', '49.00')
        ->set('variants', [
            ['id' => null, 'size' => 'M', 'color' => 'Putih', 'sku' => 'AIR-WHT-M1', 'stock' => 4],
            ['id' => null, 'size' => 'M', 'color' => 'Putih', 'sku' => 'AIR-WHT-M2', 'stock' => 5],
        ])
        ->call('saveProduct')
        ->assertHasErrors('variants');

    expect(Product::query()->count())->toBe(0);
});

test('an admin can edit product details and variant stock', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'name' => 'Blouse Lama',
        'price' => 49,
    ]);
    $variant = $product->variants()->create([
        'size' => 'M',
        'color' => 'Hitam',
        'sku' => 'BLS-BLK-M',
        'stock' => 4,
    ]);

    $this->actingAs($user)
        ->get(route('admin.products.edit', $product))
        ->assertSuccessful()
        ->assertSee('Kemas kini produk')
        ->assertSee('Blouse Lama');

    Livewire::actingAs($user)
        ->test('pages::admin.products', ['product' => $product])
        ->assertSet('editingProductId', $product->id)
        ->assertSet('name', 'Blouse Lama')
        ->set('name', 'Blouse Aira Baharu')
        ->set('price', '69.90')
        ->set('variants.0.stock', 15)
        ->call('saveProduct')
        ->assertHasNoErrors()
        ->assertRedirectToRoute('admin.products');

    expect($product->refresh()->name)->toBe('Blouse Aira Baharu')
        ->and((float) $product->price)->toBe(69.90)
        ->and($variant->refresh()->stock)->toBe(15);
});

test('an admin sees a validation error for an invalid product image', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('pages::admin.products')
        ->set('image', UploadedFile::fake()->create('dokumen.txt', 100, 'text/plain'))
        ->assertHasErrors('image')
        ->assertSee('Fail yang dipilih mestilah gambar yang sah.');
});
