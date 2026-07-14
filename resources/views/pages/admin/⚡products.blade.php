<?php

use App\Models\Product;
use App\Models\ProductVariant;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Title('Urus Produk')] class extends Component {
    use WithFileUploads;
    use WithPagination;

    public ?int $editingProductId = null;
    public string $name = '';
    public string $description = '';
    public string $price = '';
    public bool $isActive = true;
    public $image = null;

    /** @var list<array{id: int|null, size: string, color: string, sku: string, stock: int|string}> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        $this->resetProductForm();

        if ($product !== null) {
            $this->editProduct($product);
        }
    }

    #[Computed]
    public function products()
    {
        return Product::query()
            ->with('variants')
            ->latest()
            ->paginate(8);
    }

    /** @return array<string, list<mixed>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'isActive' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.size' => ['required', 'string', 'max:50'],
            'variants.*.color' => ['required', 'string', 'max:100'],
            'variants.*.sku' => ['required', 'string', 'max:100', 'distinct'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'image.image' => 'Fail yang dipilih mestilah gambar yang sah.',
            'image.mimes' => 'Gambar mesti menggunakan format JPG, JPEG, PNG atau WebP.',
            'image.max' => 'Saiz gambar tidak boleh melebihi 2MB.',
        ];
    }

    public function updatedImage(): void
    {
        Gate::authorize('manage-store');

        $this->validateOnly('image');
    }

    public function addVariant(): void
    {
        Gate::authorize('manage-store');

        $this->variants[] = ['id' => null, 'size' => '', 'color' => '', 'sku' => '', 'stock' => 0];
    }

    public function removeVariant(int $index): void
    {
        Gate::authorize('manage-store');

        if (count($this->variants) === 1) {
            $this->addError('variants', 'Produk mesti mempunyai sekurang-kurangnya satu variasi.');

            return;
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function editProduct(Product $product): void
    {
        Gate::authorize('manage-store');

        $product->load('variants');
        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->isActive = $product->is_active;
        $this->image = null;
        $this->variants = $product->variants
            ->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'sku' => $variant->sku,
                'stock' => $variant->stock,
            ])
            ->values()
            ->all();
        $this->resetValidation();
    }

    public function saveProduct(): void
    {
        Gate::authorize('manage-store');

        $wasEditing = $this->editingProductId !== null;
        $validated = $this->validate();
        $variantKeys = collect($validated['variants'])
            ->map(fn (array $variant): string => Str::lower(Str::squish($variant['size'])).'|'.Str::lower(Str::squish($variant['color'])));

        if ($variantKeys->duplicates()->isNotEmpty()) {
            $this->addError('variants', 'Gabungan saiz dan warna tidak boleh berulang.');

            return;
        }

        $variantIds = collect($validated['variants'])->pluck('id')->filter()->all();
        $hasExistingSku = ProductVariant::query()
            ->whereIn('sku', collect($validated['variants'])->pluck('sku'))
            ->when($variantIds !== [], fn ($query) => $query->whereNotIn('id', $variantIds))
            ->exists();

        if ($hasExistingSku) {
            $this->addError('variants', 'SKU mesti unik dan salah satu SKU telah digunakan.');

            return;
        }

        $storedImagePath = $this->image?->store('products', 'public');

        DB::transaction(function () use ($validated, $storedImagePath): void {
            $product = $this->editingProductId
                ? Product::query()->findOrFail($this->editingProductId)
                : new Product();

            $oldImagePath = $product->image_path;
            $product->fill([
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name']),
                'description' => $validated['description'],
                'price' => $validated['price'],
                'is_active' => $validated['isActive'],
            ]);

            if ($storedImagePath !== null) {
                $product->image_path = $storedImagePath;
            }

            $product->save();

            $keptVariantIds = [];
            foreach ($validated['variants'] as $variantData) {
                $variantId = $variantData['id'] ?? null;
                unset($variantData['id']);

                $variant = $variantId
                    ? $product->variants()->findOrFail($variantId)
                    : $product->variants()->make();

                $variant->fill($variantData);
                $variant->save();
                $keptVariantIds[] = $variant->id;
            }

            $product->variants()->whereNotIn('id', $keptVariantIds)->delete();

            if ($storedImagePath !== null && $oldImagePath !== null) {
                Storage::disk('public')->delete($oldImagePath);
            }
        });

        Flux::toast(variant: 'success', text: $wasEditing ? 'Produk dikemas kini.' : 'Produk berjaya ditambah.');

        if ($wasEditing) {
            $this->redirectRoute('admin.products', navigate: true);

            return;
        }

        $this->resetProductForm();
        unset($this->products);
    }

    public function deleteProduct(Product $product): void
    {
        Gate::authorize('manage-store');

        $imagePath = $product->image_path;
        $product->delete();

        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        Flux::toast(variant: 'success', text: 'Produk telah dipadam.');
        unset($this->products);
    }

    public function resetProductForm(): void
    {
        $this->editingProductId = null;
        $this->name = '';
        $this->description = '';
        $this->price = '';
        $this->isActive = true;
        $this->image = null;
        $this->variants = [['id' => null, 'size' => '', 'color' => '', 'sku' => '', 'stock' => 0]];
        $this->resetValidation();
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::query()
            ->where('slug', $slug)
            ->when($this->editingProductId, fn ($query) => $query->whereKeyNot($this->editingProductId))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}; ?>

<div class="flex w-full flex-1 flex-col gap-8">
    <div>
        <flux:heading size="xl">Produk & stok</flux:heading>
        <flux:text class="mt-1">Tambah pakaian dan asingkan stok bagi setiap saiz serta warna.</flux:text>
    </div>

    <flux:card id="product-form" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="lg">{{ $editingProductId ? 'Kemas kini produk' : 'Tambah produk baharu' }}</flux:heading>
                <flux:text>{{ $editingProductId ? 'Perubahan akan dipaparkan pada katalog.' : 'Isi maklumat dan sekurang-kurangnya satu variasi.' }}</flux:text>
            </div>
            @if ($editingProductId)
                <flux:button wire:click="resetProductForm" variant="ghost">Batal suntingan</flux:button>
            @endif
        </div>

        <form wire:submit="saveProduct" class="space-y-6">
            <div class="grid gap-5 lg:grid-cols-2">
                <flux:input wire:model="name" label="Nama produk" required />
                <flux:input wire:model="price" label="Harga (RM)" type="number" min="0.01" step="0.01" required />
                <flux:textarea wire:model="description" label="Penerangan" rows="4" required class="lg:col-span-2" />
                <div data-upload-container>
                    <flux:input wire:model="image" type="file" accept="image/jpeg,image/png,image/webp" label="Gambar produk (maksimum 2MB)" />
                    <flux:error name="image" />
                    <p wire:loading wire:target="image" class="mt-2 text-sm text-zinc-500">Sedang memuat naik gambar...</p>
                    <p data-upload-error hidden class="mt-2 text-sm font-medium text-red-600 dark:text-red-400">
                        Gambar gagal dimuat naik. Pastikan formatnya JPG, PNG atau WebP, saiz tidak melebihi 2MB, kemudian cuba lagi.
                    </p>
                </div>
                <div class="flex items-end pb-2"><flux:checkbox wire:model="isActive" label="Paparkan produk dalam katalog" /></div>
            </div>

            <flux:separator />

            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading>Variasi produk</flux:heading>
                    <flux:text>Setiap gabungan saiz dan warna mempunyai stok tersendiri.</flux:text>
                </div>
                <flux:button type="button" wire:click="addVariant" icon="plus">Tambah variasi</flux:button>
            </div>

            <flux:error name="variants" />

            <div class="space-y-4">
                @foreach ($variants as $index => $variant)
                    <div wire:key="variant-form-{{ $variant['id'] ?? 'new-'.$index }}" class="grid gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700 sm:grid-cols-2 lg:grid-cols-[1fr_1.4fr_1.4fr_1fr_auto]">
                        <flux:input wire:model="variants.{{ $index }}.size" label="Saiz" placeholder="M" required />
                        <flux:input wire:model="variants.{{ $index }}.color" label="Warna" placeholder="Hitam" required />
                        <flux:input wire:model="variants.{{ $index }}.sku" label="SKU" placeholder="BJU-BLK-M" required />
                        <flux:input wire:model="variants.{{ $index }}.stock" label="Stok" type="number" min="0" required />
                        <div class="flex items-end"><flux:button type="button" wire:click="removeVariant({{ $index }})" variant="danger" icon="trash" square aria-label="Padam variasi" /></div>
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end">
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveProduct">{{ $editingProductId ? 'Simpan perubahan' : 'Tambah produk' }}</span>
                    <span wire:loading wire:target="saveProduct">Menyimpan...</span>
                </flux:button>
            </div>
        </form>
    </flux:card>

    <section>
        <div class="mb-4 flex items-center justify-between gap-4">
            <flux:heading size="lg">Senarai produk</flux:heading>
            <flux:text>{{ $this->products->total() }} produk</flux:text>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            @foreach ($this->products as $product)
                <flux:card wire:key="admin-product-{{ $product->id }}" class="flex flex-col gap-5">
                    <div class="flex items-start gap-4">
                        @if ($product->image_path)
                            <img src="{{ Storage::url($product->image_path) }}" alt="" class="size-20 rounded-xl object-cover">
                        @else
                            <div class="grid size-20 shrink-0 place-items-center rounded-xl bg-rose-100 text-2xl font-bold text-rose-500 dark:bg-rose-950">{{ Str::upper(Str::substr($product->name, 0, 1)) }}</div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <flux:heading>{{ $product->name }}</flux:heading>
                                <flux:badge :color="$product->is_active ? 'green' : 'zinc'">{{ $product->is_active ? 'Aktif' : 'Tidak aktif' }}</flux:badge>
                            </div>
                            <p class="mt-1 font-semibold text-rose-600">{{ Number::currency((float) $product->price, in: 'MYR', locale: 'ms') }}</p>
                            <flux:text class="mt-1">{{ $product->variants->sum('stock') }} jumlah stok · {{ $product->variants->count() }} variasi</flux:text>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->variants as $variant)
                            <flux:badge wire:key="admin-variant-{{ $variant->id }}" color="zinc">{{ $variant->size }} / {{ $variant->color }}: {{ $variant->stock }}</flux:badge>
                        @endforeach
                    </div>

                    <div class="mt-auto flex justify-end gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                        <flux:button :href="route('admin.products.edit', $product)" icon="pencil-square" wire:navigate>Sunting</flux:button>
                        <flux:button wire:click="deleteProduct({{ $product->id }})" wire:confirm="Padam produk ini? Pesanan lama akan kekal." variant="danger" icon="trash">Padam</flux:button>
                    </div>
                </flux:card>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->products->links() }}</div>
    </section>
</div>
