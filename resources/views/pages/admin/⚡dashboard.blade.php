<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\OrderStatus;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    /** @return array{products: int, pending: int, low_stock: int, revenue: float} */
    #[Computed]
    public function stats(): array
    {
        return [
            'products' => Product::query()->where('is_active', true)->count(),
            'pending' => Order::query()->where('status', OrderStatus::PendingPayment)->count(),
            'low_stock' => ProductVariant::query()->where('stock', '<=', 5)->count(),
            'revenue' => (float) Order::query()
                ->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed])
                ->sum('total'),
        ];
    }

    #[Computed]
    public function latestOrders()
    {
        return Order::query()->latest()->limit(5)->get();
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Ringkasan jualan</flux:heading>
            <flux:text class="mt-1">Pantau produk, stok dan tempahan terkini.</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('admin.products')" icon="plus" wire:navigate>Tambah produk</flux:button>
            <flux:button :href="route('admin.orders')" variant="primary" wire:navigate>Lihat tempahan</flux:button>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <flux:card>
            <flux:text>Produk aktif</flux:text>
            <p class="mt-2 text-3xl font-bold">{{ $this->stats['products'] }}</p>
        </flux:card>
        <flux:card>
            <flux:text>Menunggu pembayaran</flux:text>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $this->stats['pending'] }}</p>
        </flux:card>
        <flux:card>
            <flux:text>Variasi stok rendah</flux:text>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $this->stats['low_stock'] }}</p>
        </flux:card>
        <flux:card>
            <flux:text>Jualan disahkan</flux:text>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ Number::currency($this->stats['revenue'], in: 'MYR', locale: 'ms') }}</p>
        </flux:card>
    </div>

    <flux:card>
        <div class="mb-5 flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">Tempahan terkini</flux:heading>
                <flux:text>Lima tempahan terbaru daripada pelanggan.</flux:text>
            </div>
            <flux:button :href="route('admin.orders')" variant="ghost" wire:navigate>Lihat semua</flux:button>
        </div>

        @if ($this->latestOrders->isEmpty())
            <div class="rounded-xl border border-dashed border-zinc-300 py-12 text-center dark:border-zinc-700">
                <flux:text>Belum ada tempahan.</flux:text>
            </div>
        @else
            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach ($this->latestOrders as $order)
                    <div wire:key="dashboard-order-{{ $order->id }}" class="flex flex-wrap items-center justify-between gap-4 py-4 first:pt-0 last:pb-0">
                        <div>
                            <p class="font-semibold">{{ $order->order_number }}</p>
                            <p class="mt-1 text-sm text-zinc-500">{{ $order->customer_name }} · {{ $order->product_name }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <flux:badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
                            <p class="min-w-24 text-right font-semibold">{{ Number::currency((float) $order->total, in: 'MYR', locale: 'ms') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </flux:card>
</div>
