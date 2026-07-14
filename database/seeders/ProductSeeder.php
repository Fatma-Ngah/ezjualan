<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Kurung Moden Seri',
                'slug' => 'kurung-moden-seri',
                'description' => 'Baju kurung moden yang selesa dengan potongan kemas untuk kegunaan harian dan majlis.',
                'price' => 89.00,
                'variants' => [
                    ['size' => 'S', 'color' => 'Dusty Pink', 'sku' => 'KMS-DP-S', 'stock' => 12],
                    ['size' => 'M', 'color' => 'Dusty Pink', 'sku' => 'KMS-DP-M', 'stock' => 15],
                    ['size' => 'L', 'color' => 'Sage Green', 'sku' => 'KMS-SG-L', 'stock' => 9],
                ],
            ],
            [
                'name' => 'Blouse Linen Aira',
                'slug' => 'blouse-linen-aira',
                'description' => 'Blouse linen ringan dengan rekaan minimal yang mudah dipadankan untuk gaya kasual.',
                'price' => 59.00,
                'variants' => [
                    ['size' => 'S', 'color' => 'Putih', 'sku' => 'BLA-WH-S', 'stock' => 10],
                    ['size' => 'M', 'color' => 'Putih', 'sku' => 'BLA-WH-M', 'stock' => 14],
                    ['size' => 'L', 'color' => 'Hitam', 'sku' => 'BLA-BK-L', 'stock' => 8],
                ],
            ],
            [
                'name' => 'Jubah Hana Premium',
                'slug' => 'jubah-hana-premium',
                'description' => 'Jubah berpotongan anggun menggunakan fabrik lembut dan tidak mudah berkedut.',
                'price' => 119.00,
                'variants' => [
                    ['size' => 'M', 'color' => 'Navy', 'sku' => 'JHP-NV-M', 'stock' => 7],
                    ['size' => 'L', 'color' => 'Navy', 'sku' => 'JHP-NV-L', 'stock' => 11],
                    ['size' => 'XL', 'color' => 'Mocha', 'sku' => 'JHP-MC-XL', 'stock' => 6],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $variants = $productData['variants'];
            unset($productData['variants']);

            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                $productData + ['is_active' => true],
            );

            foreach ($variants as $variant) {
                $product->variants()->updateOrCreate(['sku' => $variant['sku']], $variant);
            }
        }
    }
}
