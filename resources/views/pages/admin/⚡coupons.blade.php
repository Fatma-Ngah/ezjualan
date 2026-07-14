<?php

use App\Models\Coupon;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Urus Kupon')] class extends Component {
    use WithPagination;

    public ?int $editingCouponId = null;
    public string $code = '';
    public string $percentage = '';
    public string $startsAt = '';
    public string $expiresAt = '';
    public bool $isActive = true;

    #[Computed]
    public function coupons()
    {
        return Coupon::query()->latest()->paginate(10);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique(Coupon::class, 'code')->ignore($this->editingCouponId),
            ],
            'percentage' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'startsAt' => ['nullable', 'date'],
            'expiresAt' => ['nullable', 'date', 'after_or_equal:startsAt'],
            'isActive' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'code.regex' => 'Kod kupon hanya boleh mengandungi huruf, nombor, tanda sempang dan garis bawah.',
            'code.unique' => 'Kod kupon ini telah digunakan.',
            'percentage.min' => 'Peratus diskaun mestilah sekurang-kurangnya 0.01%.',
            'percentage.max' => 'Peratus diskaun tidak boleh melebihi 100%.',
            'expiresAt.after_or_equal' => 'Tarikh tamat mestilah selepas atau sama dengan tarikh mula.',
        ];
    }

    public function saveCoupon(): void
    {
        Gate::authorize('manage-store');

        $this->code = Str::upper(Str::squish($this->code));
        $validated = $this->validate();
        $coupon = $this->editingCouponId
            ? Coupon::query()->findOrFail($this->editingCouponId)
            : new Coupon();
        $wasEditing = $coupon->exists;

        $coupon->fill([
            'code' => $validated['code'],
            'percentage' => $validated['percentage'],
            'starts_at' => filled($validated['startsAt']) ? $validated['startsAt'] : null,
            'expires_at' => filled($validated['expiresAt']) ? $validated['expiresAt'] : null,
            'is_active' => $validated['isActive'],
        ])->save();

        Flux::toast(variant: 'success', text: $wasEditing ? 'Kupon telah dikemas kini.' : 'Kupon berjaya ditambah.');
        $this->resetCouponForm();
        unset($this->coupons);
    }

    public function editCoupon(Coupon $coupon): void
    {
        Gate::authorize('manage-store');

        $this->editingCouponId = $coupon->id;
        $this->code = $coupon->code;
        $this->percentage = $coupon->percentage;
        $this->startsAt = $coupon->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->expiresAt = $coupon->expires_at?->format('Y-m-d\TH:i') ?? '';
        $this->isActive = $coupon->is_active;
        $this->resetValidation();
    }

    public function deleteCoupon(Coupon $coupon): void
    {
        Gate::authorize('manage-store');

        $coupon->delete();
        Flux::toast(variant: 'success', text: 'Kupon telah dipadam.');

        if ($this->editingCouponId === $coupon->id) {
            $this->resetCouponForm();
        }

        unset($this->coupons);
    }

    public function resetCouponForm(): void
    {
        $this->editingCouponId = null;
        $this->code = '';
        $this->percentage = '';
        $this->startsAt = '';
        $this->expiresAt = '';
        $this->isActive = true;
        $this->resetValidation();
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-8">
    <div>
        <flux:heading size="xl">Kupon diskaun</flux:heading>
        <flux:text class="mt-1">Cipta kod diskaun peratus untuk semua produk di kedai.</flux:text>
    </div>

    <flux:card class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $editingCouponId ? 'Kemas kini kupon' : 'Tambah kupon baharu' }}</flux:heading>
                <flux:text>Tarikh mula dan tamat boleh dibiarkan kosong.</flux:text>
            </div>
            @if ($editingCouponId)
                <flux:button wire:click="resetCouponForm" variant="ghost">Batal suntingan</flux:button>
            @endif
        </div>

        <form wire:submit="saveCoupon" class="space-y-6">
            <div class="grid gap-5 md:grid-cols-2">
                <flux:input wire:model="code" label="Kod kupon" placeholder="JIMAT10" required />
                <flux:input wire:model="percentage" label="Diskaun (%)" type="number" min="0.01" max="100" step="0.01" required />
                <flux:input wire:model="startsAt" label="Tarikh mula (pilihan)" type="datetime-local" />
                <flux:input wire:model="expiresAt" label="Tarikh tamat (pilihan)" type="datetime-local" />
                <div class="md:col-span-2">
                    <flux:checkbox wire:model="isActive" label="Kupon aktif dan boleh digunakan" />
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveCoupon">{{ $editingCouponId ? 'Simpan perubahan' : 'Tambah kupon' }}</span>
                    <span wire:loading wire:target="saveCoupon">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </flux:card>

    <section>
        <div class="mb-4 flex items-center justify-between gap-4">
            <flux:heading size="lg">Senarai kupon</flux:heading>
            <flux:text>{{ $this->coupons->total() }} kupon</flux:text>
        </div>

        @if ($this->coupons->isEmpty())
            <flux:card class="py-16 text-center">
                <flux:heading>Belum ada kupon</flux:heading>
                <flux:text class="mt-2">Kupon yang ditambah akan dipaparkan di sini.</flux:text>
            </flux:card>
        @else
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($this->coupons as $coupon)
                    <flux:card wire:key="coupon-{{ $coupon->id }}" class="flex flex-col gap-5">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <flux:heading size="lg" class="font-mono">{{ $coupon->code }}</flux:heading>
                                <p class="mt-1 text-2xl font-bold text-rose-600">{{ Number::percentage((float) $coupon->percentage) }}</p>
                            </div>
                            <flux:badge :color="$coupon->is_active ? 'green' : 'zinc'">{{ $coupon->is_active ? 'Aktif' : 'Tidak aktif' }}</flux:badge>
                        </div>

                        <div class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <p class="text-zinc-500">Mula</p>
                                <p class="font-medium">{{ $coupon->starts_at?->format('d/m/Y, g:i A') ?? 'Tiada had' }}</p>
                            </div>
                            <div>
                                <p class="text-zinc-500">Tamat</p>
                                <p class="font-medium">{{ $coupon->expires_at?->format('d/m/Y, g:i A') ?? 'Tiada had' }}</p>
                            </div>
                        </div>

                        <div class="mt-auto flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                            <flux:button wire:click="editCoupon({{ $coupon->id }})" icon="pencil-square">Sunting</flux:button>
                            <flux:button wire:click="deleteCoupon({{ $coupon->id }})" wire:confirm="Padam kupon ini? Rekod diskaun tempahan lama akan kekal." variant="danger" icon="trash">Padam</flux:button>
                        </div>
                    </flux:card>
                @endforeach
            </div>

            <div class="mt-6">{{ $this->coupons->links() }}</div>
        @endif
    </section>
</div>
