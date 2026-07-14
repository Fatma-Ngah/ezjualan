<?php

use App\Actions\CreateOrder;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('components.layouts.store'), Title('Tempah Produk')] class extends Component {
    public Product $product;
    public ?int $selectedVariantId = null;
    public int $quantity = 1;
    public string $customerName = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public string $postcode = '';
    public string $city = '';
    public string $state = '';
    public string $shippingNote = '';
    public string $couponCode = '';
    public ?int $appliedCouponId = null;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product->load(['variants' => fn ($query) => $query->where('stock', '>', 0)->orderBy('size')->orderBy('color')]);
        $this->selectedVariantId = $this->product->variants->first()?->id;
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'selectedVariantId' => [
                'required',
                'integer',
                Rule::exists(ProductVariant::class, 'id')->where('product_id', $this->product->id),
            ],
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'customerName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'address' => ['required', 'string', 'max:1000'],
            'postcode' => ['required', 'digits:5'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', Rule::in(array_keys(config('shipping.rates')))],
            'shippingNote' => ['nullable', 'string', 'max:500'],
        ];
    }

    #[Computed]
    public function selectedVariant(): ?ProductVariant
    {
        return $this->product->variants->firstWhere('id', $this->selectedVariantId);
    }

    #[Computed]
    public function shippingFee(): float
    {
        return (float) (config('shipping.rates.'.$this->state) ?? 0);
    }

    #[Computed]
    public function appliedCoupon(): ?Coupon
    {
        return $this->appliedCouponId === null
            ? null
            : Coupon::query()->find($this->appliedCouponId);
    }

    #[Computed]
    public function discount(): float
    {
        $coupon = $this->appliedCoupon;

        if ($coupon === null || $coupon->validationMessage() !== null) {
            return 0;
        }

        return $coupon->discountInCents($this->subtotalInCents()) / 100;
    }

    #[Computed]
    public function total(): float
    {
        return ($this->subtotalInCents() / 100) - $this->discount + $this->shippingFee;
    }

    public function updatedCouponCode(): void
    {
        if ($this->appliedCoupon !== null && Str::upper(Str::squish($this->couponCode)) !== $this->appliedCoupon->code) {
            $this->removeCoupon();
        }
    }

    public function applyCoupon(): void
    {
        $this->validateOnly('couponCode', [
            'couponCode' => ['required', 'string', 'max:255'],
        ]);

        $coupon = Coupon::query()
            ->where('code', Str::upper(Str::squish($this->couponCode)))
            ->first();

        if ($coupon === null) {
            $this->addError('couponCode', 'Kod kupon tidak sah.');

            return;
        }

        if ($message = $coupon->validationMessage()) {
            $this->addError('couponCode', $message);

            return;
        }

        $this->couponCode = $coupon->code;
        $this->appliedCouponId = $coupon->id;
        $this->resetErrorBag('couponCode');
        unset($this->appliedCoupon, $this->discount, $this->total);
    }

    public function removeCoupon(): void
    {
        $this->appliedCouponId = null;
        $this->resetErrorBag('couponCode');
        unset($this->appliedCoupon, $this->discount, $this->total);
    }

    public function placeOrder(CreateOrder $createOrder): void
    {
        $validated = $this->validate();
        $variant = ProductVariant::query()->findOrFail($validated['selectedVariantId']);

        $order = $createOrder->handle($variant, [
            'quantity' => $validated['quantity'],
            'customer_name' => $validated['customerName'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'address' => $validated['address'],
            'postcode' => $validated['postcode'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'shipping_note' => $validated['shippingNote'],
            'coupon_code' => $this->appliedCouponId !== null ? $this->couponCode : null,
        ]);

        $this->redirectRoute('orders.confirmation', $order, navigate: true);
    }

    private function subtotalInCents(): int
    {
        return (int) round(((float) $this->product->price) * 100) * $this->quantity;
    }
}; ?>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="{{ route('home') }}" class="mb-8 inline-flex items-center gap-2 text-sm font-medium text-zinc-500 hover:text-rose-600" wire:navigate>
        <span aria-hidden="true">&larr;</span> Kembali ke koleksi
    </a>

    <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.05fr)]">
        <section>
            <div class="sticky top-24">
                @if ($product->image_path)
                    <img src="{{ Storage::url($product->image_path) }}" alt="{{ $product->name }}" class="aspect-[4/3] w-full rounded-3xl object-cover shadow-sm">
                @else
                    <div class="grid aspect-[4/3] place-items-center rounded-3xl bg-gradient-to-br from-rose-100 via-orange-50 to-amber-100 dark:from-rose-950 dark:via-zinc-900 dark:to-amber-950">
                        <span class="text-9xl font-black text-rose-300/80 dark:text-rose-800">{{ Str::upper(Str::substr($product->name, 0, 1)) }}</span>
                    </div>
                @endif

                <div class="mt-6">
                    <p class="text-sm font-semibold uppercase tracking-wider text-rose-600">Sedia ditempah</p>
                    <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $product->name }}</h1>
                    <p class="mt-3 text-2xl font-semibold">{{ Number::currency((float) $product->price, in: 'MYR', locale: 'ms') }}</p>
                    <p class="mt-5 leading-7 text-zinc-600 dark:text-zinc-300">{{ $product->description }}</p>
                </div>
            </div>
        </section>

        <form wire:submit="placeOrder" class="space-y-8">
            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                <div class="mb-6">
                    <flux:heading size="xl">Pilihan produk</flux:heading>
                    <flux:text class="mt-1">Pilih variasi dan kuantiti yang anda mahu.</flux:text>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:select wire:model.live="selectedVariantId" label="Saiz dan warna" required>
                        <option value="">Pilih variasi</option>
                        @foreach ($product->variants as $variant)
                            <option wire:key="variant-{{ $variant->id }}" value="{{ $variant->id }}">{{ $variant->size }} / {{ $variant->color }} ({{ $variant->stock }} stok)</option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model.live="quantity" type="number" min="1" max="10" label="Kuantiti" required />
                </div>

                @if ($this->selectedVariant)
                    <flux:callout class="mt-5" icon="information-circle">
                        {{ $this->selectedVariant->stock }} unit tersedia untuk {{ $this->selectedVariant->size }} / {{ $this->selectedVariant->color }}.
                    </flux:callout>
                @endif
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                <div class="mb-6">
                    <flux:heading size="xl">Maklumat penghantaran</flux:heading>
                    <flux:text class="mt-1">Semua ruangan diwajibkan kecuali nota penghantaran.</flux:text>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <flux:input wire:model="customerName" label="Nama penerima" autocomplete="name" required />
                    <flux:input wire:model="phone" label="Nombor telefon" type="tel" autocomplete="tel" placeholder="0123456789" required />
                    <flux:input wire:model="email" label="E-mel" type="email" autocomplete="email" required class="sm:col-span-2" />
                    <flux:textarea wire:model="address" label="Alamat penuh" rows="3" required class="sm:col-span-2" />
                    <flux:input wire:model="postcode" label="Poskod" inputmode="numeric" maxlength="5" required />
                    <flux:input wire:model="city" label="Bandar" required />
                    <flux:select wire:model.live="state" label="Negeri" required class="sm:col-span-2">
                        <option value="">Pilih negeri</option>
                        @foreach (config('shipping.rates') as $stateName => $rate)
                            <option value="{{ $stateName }}">{{ $stateName }} — {{ Number::currency((float) $rate, in: 'MYR', locale: 'ms') }}</option>
                        @endforeach
                    </flux:select>
                    <flux:textarea wire:model="shippingNote" label="Nota penghantaran (pilihan)" rows="2" class="sm:col-span-2" />
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-8">
                <div class="mb-5">
                    <flux:heading size="xl">Kupon diskaun</flux:heading>
                    <flux:text class="mt-1">Masukkan kod kupon jika anda memilikinya.</flux:text>
                </div>

                <div class="flex items-end gap-3">
                    <flux:input wire:model.live.debounce.300ms="couponCode" label="Kod kupon" placeholder="Contoh: JIMAT10" class="flex-1" :disabled="$appliedCouponId !== null" />
                    @if ($appliedCouponId)
                        <flux:button type="button" wire:click="removeCoupon" variant="ghost">Buang</flux:button>
                    @else
                        <flux:button type="button" wire:click="applyCoupon" wire:loading.attr="disabled" wire:target="applyCoupon">Gunakan</flux:button>
                    @endif
                </div>

                @if ($this->appliedCoupon)
                    <flux:callout class="mt-4" variant="success" icon="check-circle">
                        Kupon {{ $this->appliedCoupon->code }} memberikan diskaun {{ Number::percentage((float) $this->appliedCoupon->percentage) }}.
                    </flux:callout>
                @endif
            </section>

            <section class="rounded-2xl bg-zinc-950 p-6 text-white shadow-xl dark:bg-white dark:text-zinc-950 sm:p-8">
                <flux:heading size="xl" class="!text-current">Ringkasan tempahan</flux:heading>
                <div class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4"><span class="opacity-70">Produk × {{ $quantity }}</span><span>{{ Number::currency(((float) $product->price) * $quantity, in: 'MYR', locale: 'ms') }}</span></div>
                    @if ($this->discount > 0)
                        <div class="flex justify-between gap-4 text-emerald-400 dark:text-emerald-600"><span>Diskaun ({{ $this->appliedCoupon?->code }})</span><span>-{{ Number::currency($this->discount, in: 'MYR', locale: 'ms') }}</span></div>
                    @endif
                    <div class="flex justify-between gap-4"><span class="opacity-70">Penghantaran {{ $state ? 'ke '.$state : '' }}</span><span>{{ $state ? Number::currency($this->shippingFee, in: 'MYR', locale: 'ms') : 'Pilih negeri' }}</span></div>
                    <div class="flex justify-between gap-4 border-t border-white/20 pt-4 text-lg font-bold dark:border-zinc-900/20"><span>Jumlah</span><span>{{ Number::currency($this->total, in: 'MYR', locale: 'ms') }}</span></div>
                </div>

                <p class="mt-5 text-xs leading-5 opacity-65">Pembayaran diurus secara manual. Tempahan akan berstatus “Menunggu Pembayaran” sehingga disahkan oleh admin.</p>
                <flux:button type="submit" variant="primary" class="mt-6 w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="placeOrder">Buat tempahan</span>
                    <span wire:loading wire:target="placeOrder">Memproses...</span>
                </flux:button>
            </section>
        </form>
    </div>
</div>
