<?php

use App\Models\Order;
use Illuminate\Support\Number;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.store'), Title('Tempahan Diterima')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        $this->order = $order;
    }
}; ?>

<div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
        <div class="bg-emerald-50 px-6 py-10 text-center dark:bg-emerald-950/30 sm:px-10">
            <div class="mx-auto grid size-14 place-items-center rounded-full bg-emerald-600 text-2xl text-white">✓</div>
            <h1 class="mt-5 text-3xl font-bold tracking-tight">Tempahan diterima</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-300">Simpan nombor tempahan ini untuk rujukan anda.</p>
            <p class="mt-4 font-mono text-xl font-bold text-emerald-700 dark:text-emerald-400">{{ $order->order_number }}</p>
        </div>

        <div class="space-y-8 p-6 sm:p-10">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-zinc-500">Status semasa</p>
                    <p class="mt-1 font-semibold">{{ $order->status->label() }}</p>
                </div>
                <flux:badge :color="$order->status->badgeColor()" size="lg">{{ $order->status->label() }}</flux:badge>
            </div>

            <div class="grid gap-6 border-y border-zinc-200 py-6 dark:border-zinc-800 sm:grid-cols-2">
                <div>
                    <h2 class="font-semibold">Butiran produk</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between gap-4"><dt class="text-zinc-500">Produk</dt><dd class="text-right font-medium">{{ $order->product_name }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-zinc-500">Variasi</dt><dd>{{ $order->size }} / {{ $order->color }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-zinc-500">Kuantiti</dt><dd>{{ $order->quantity }}</dd></div>
                    </dl>
                </div>

                <div>
                    <h2 class="font-semibold">Penghantaran</h2>
                    <address class="mt-3 text-sm not-italic leading-6 text-zinc-600 dark:text-zinc-300">
                        {{ $order->customer_name }}<br>
                        {{ $order->phone }}<br>
                        {{ $order->address }}, {{ $order->postcode }} {{ $order->city }}, {{ $order->state }}
                    </address>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">Subtotal</dt><dd>{{ Number::currency(((float) $order->unit_price) * $order->quantity, in: 'MYR', locale: 'ms') }}</dd></div>
                @if ((float) $order->discount_amount > 0)
                    <div class="flex justify-between gap-4 text-emerald-600 dark:text-emerald-400"><dt>Diskaun ({{ $order->coupon_code }})</dt><dd>-{{ Number::currency((float) $order->discount_amount, in: 'MYR', locale: 'ms') }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-zinc-500">Caj penghantaran</dt><dd>{{ Number::currency((float) $order->shipping_fee, in: 'MYR', locale: 'ms') }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-zinc-200 pt-4 text-lg font-bold dark:border-zinc-800"><dt>Jumlah</dt><dd>{{ Number::currency((float) $order->total, in: 'MYR', locale: 'ms') }}</dd></div>
            </dl>

            <flux:callout variant="warning" icon="clock">
                Pembayaran akan disahkan secara manual oleh admin. Stok hanya ditolak selepas status ditukar kepada “Telah Dibayar”.
            </flux:callout>

            <flux:button :href="route('home')" variant="primary" class="w-full" wire:navigate>Kembali ke koleksi</flux:button>
        </div>
    </div>
</div>
