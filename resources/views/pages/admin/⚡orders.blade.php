<?php

use App\Actions\UpdateOrderStatus;
use App\Models\Order;
use App\OrderStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Urus Tempahan')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders()
    {
        return Order::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($query): void {
                    $query->where('order_number', 'like', '%'.$this->search.'%')
                        ->orWhere('customer_name', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);
    }

    public function updateStatus(int $orderId, string $status, UpdateOrderStatus $updateOrderStatus): void
    {
        Gate::authorize('manage-store');

        $order = Order::query()->findOrFail($orderId);
        $newStatus = OrderStatus::tryFrom($status);

        abort_if($newStatus === null, 422);

        $updateOrderStatus->handle($order, $newStatus);
        Flux::toast(variant: 'success', text: 'Status tempahan telah dikemas kini.');
        unset($this->orders);
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-6">
    <div>
        <flux:heading size="xl">Tempahan pelanggan</flux:heading>
        <flux:text class="mt-1">Sahkan pembayaran manual dan kemas kini proses penghantaran.</flux:text>
    </div>

    <flux:card class="grid gap-4 md:grid-cols-[1fr_16rem]">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Cari nombor, nama atau telefon..." label="Carian" />
        <flux:select wire:model.live="statusFilter" label="Status">
            <option value="">Semua status</option>
            @foreach (OrderStatus::cases() as $status)
                <option value="{{ $status->value }}">{{ $status->label() }}</option>
            @endforeach
        </flux:select>
    </flux:card>

    <flux:error name="selectedStatus" />

    @if ($this->orders->isEmpty())
        <flux:card class="py-16 text-center">
            <flux:heading>Tiada tempahan dijumpai</flux:heading>
            <flux:text class="mt-2">Tempahan pelanggan akan dipaparkan di sini.</flux:text>
        </flux:card>
    @else
        <div class="space-y-5">
            @foreach ($this->orders as $order)
                <flux:card wire:key="order-{{ $order->id }}" class="space-y-5">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <flux:heading size="lg">{{ $order->order_number }}</flux:heading>
                                <flux:badge :color="$order->status->badgeColor()">{{ $order->status->label() }}</flux:badge>
                            </div>
                            <flux:text class="mt-1">{{ $order->created_at->format('d/m/Y, g:i A') }}</flux:text>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold">{{ Number::currency((float) $order->total, in: 'MYR', locale: 'ms') }}</p>
                            @if ((float) $order->discount_amount > 0)
                                <p class="mt-1 text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ $order->coupon_code }}: -{{ Number::currency((float) $order->discount_amount, in: 'MYR', locale: 'ms') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-6 border-y border-zinc-200 py-5 dark:border-zinc-700 md:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Produk</p>
                            <p class="mt-2 font-medium">{{ $order->product_name }}</p>
                            <p class="mt-1 text-sm text-zinc-500">{{ $order->size }} / {{ $order->color }} · {{ $order->quantity }} unit</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Pelanggan</p>
                            <p class="mt-2 font-medium">{{ $order->customer_name }}</p>
                            <p class="mt-1 text-sm text-zinc-500">{{ $order->phone }} · {{ $order->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-zinc-500">Alamat penghantaran</p>
                            <p class="mt-2 text-sm leading-6">{{ $order->address }}, {{ $order->postcode }} {{ $order->city }}, {{ $order->state }}</p>
                            @if ($order->shipping_note)
                                <p class="mt-1 text-sm italic text-zinc-500">Nota: {{ $order->shipping_note }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <p class="text-sm text-zinc-500">
                            @if ($order->stock_deducted_at)
                                Stok telah ditolak pada {{ $order->stock_deducted_at->format('d/m/Y, g:i A') }}
                            @else
                                Stok belum ditolak
                            @endif
                        </p>

                        @if ($order->status->nextStatuses() !== [])
                            <div class="flex flex-wrap justify-end gap-2">
                                @foreach ($order->status->nextStatuses() as $nextStatus)
                                    <flux:button
                                        wire:key="status-{{ $order->id }}-{{ $nextStatus->value }}"
                                        wire:click="updateStatus({{ $order->id }}, '{{ $nextStatus->value }}')"
                                        wire:confirm="Tukar status kepada {{ $nextStatus->label() }}?"
                                        :variant="$nextStatus === OrderStatus::Cancelled ? 'danger' : 'primary'"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ $nextStatus->label() }}
                                    </flux:button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div>{{ $this->orders->links() }}</div>
    @endif
</div>
