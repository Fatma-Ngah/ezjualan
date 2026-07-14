<?php

namespace App;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Telah Dibayar',
            self::Processing => 'Sedang Diproses',
            self::Shipped => 'Telah Dihantar',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::PendingPayment => 'amber',
            self::Paid => 'green',
            self::Processing => 'blue',
            self::Shipped => 'purple',
            self::Completed => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /** @return list<self> */
    public function nextStatuses(): array
    {
        return match ($this) {
            self::PendingPayment => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped => [self::Completed],
            self::Completed, self::Cancelled => [],
        };
    }
}
