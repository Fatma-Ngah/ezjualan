<?php

use App\Models\Product;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.store'), Title('Koleksi Pakaian')] class extends Component {
    #[Computed]
    public function products()
    {
        return Product::query()
            ->where('is_active', true)
            ->whereHas('variants', fn ($query) => $query->where('stock', '>', 0))
            ->withSum('variants as available_stock', 'stock')
            ->latest()
            ->get();
    }
}; ?>

<div>
    <section class="relative overflow-hidden border-b border-rose-100 bg-rose-50 dark:border-rose-950 dark:bg-rose-950/20">
        <div class="absolute -right-24 -top-24 size-80 rounded-full bg-rose-200/60 blur-3xl dark:bg-rose-900/30"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
            <p class="mb-4 text-sm font-semibold uppercase tracking-[0.22em] text-rose-600">Koleksi terkini</p>
            <h1 class="max-w-3xl text-4xl font-bold tracking-tight text-zinc-950 sm:text-6xl dark:text-white">Gaya pilihan anda, terus ditempah.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-zinc-600 dark:text-zinc-300">Pilih pakaian, saiz dan warna. Lengkapkan maklumat penghantaran tanpa daftar akaun atau tambah ke troli.</p>
            <a href="#koleksi" class="mt-8 inline-flex rounded-xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-rose-700">Lihat koleksi</a>
        </div>
    </section>

    <section id="koleksi" class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-rose-600">Sedia untuk ditempah</p>
                <h2 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Pilihan pakaian</h2>
            </div>
            <p class="text-sm text-zinc-500">{{ $this->products->count() }} produk</p>
        </div>

        @if ($this->products->isEmpty())
            <div class="rounded-2xl border border-dashed border-zinc-300 px-6 py-20 text-center dark:border-zinc-700">
                <flux:heading size="lg">Koleksi sedang dikemas kini</flux:heading>
                <flux:text class="mt-2">Sila datang semula untuk melihat produk terbaru kami.</flux:text>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    <article wire:key="product-{{ $product->id }}" class="group overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                        <a href="{{ route('products.show', $product) }}" wire:navigate class="block">
                            @if ($product->image_path)
                                <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="grid aspect-[4/3] place-items-center bg-gradient-to-br from-rose-100 via-orange-50 to-amber-100 dark:from-rose-950 dark:via-zinc-900 dark:to-amber-950">
                                    <span class="text-7xl font-black text-rose-300/80 dark:text-rose-800">{{ Str::upper(Str::substr($product->name, 0, 1)) }}</span>
                                </div>
                            @endif
                        </a>

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <h3 class="text-lg font-semibold">{{ $product->name }}</h3>
                                <span class="shrink-0 font-semibold text-rose-600">{{ Number::currency((float) $product->price, in: 'MYR', locale: 'ms') }}</span>
                            </div>
                            <p class="mt-2 line-clamp-2 text-sm leading-6 text-zinc-500">{{ $product->description }}</p>
                            <div class="mt-5 flex items-center justify-between gap-4">
                                <span class="text-xs text-zinc-500">{{ $product->available_stock }} unit tersedia</span>
                                <flux:button :href="route('products.show', $product)" variant="primary" wire:navigate>Tempah sekarang</flux:button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>
