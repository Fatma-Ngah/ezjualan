<!DOCTYPE html>
<html lang="ms">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-stone-50 text-zinc-900 antialiased dark:bg-zinc-950 dark:text-zinc-100">
        <header class="sticky top-0 z-40 border-b border-zinc-200/80 bg-stone-50/90 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/90">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="grid size-9 place-items-center rounded-xl bg-rose-600 text-sm font-bold text-white">EZ</span>
                    <span class="font-semibold tracking-tight">EzJualan</span>
                </a>

                <nav class="flex items-center gap-2">
                    <a href="{{ route('home') }}" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-900" wire:navigate>Produk</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900" wire:navigate>Panel Admin</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-white" wire:navigate>Admin</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main>{{ $slot }}</main>

        <footer class="mt-20 border-t border-zinc-200 dark:border-zinc-800">
            <div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-8 text-sm text-zinc-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>&copy; {{ now()->year }} EzJualan. Tempahan pakaian dengan mudah.</p>
                <p>Penghantaran dari {{ config('shipping.origin') }}</p>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group><flux:toast /></flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
