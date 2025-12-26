<?php

namespace Yousefkadah\Pelecard;

use ArrayAccess;

class Invoice implements ArrayAccess
{
    public $invoice_number;
    public $invoice_date;
    public $due_date;
    public $customer;
    public $vendor;
    public $subtotal;
    public $tax;
    public $total;
    public $notes;
    public $terms;
    public $id;
    public $amount;
    public $currency;
    public $date;
    public $status;
    public $items;

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->$offset = null;
    }

    /**
     * Create a new invoice instance.
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the invoice as an array.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'date' => $this->date,
            'status' => $this->status,
            'items' => $this->items,
        ];
    }

    public function number(): ?string
    {
        return $this->id ?? $this->invoice_number ?? null;
    }

    public function date(): ?string
    {
        return $this->date ?? $this->invoice_date ?? null;
    }

    public function dueDate(): ?string
    {
        return $this->due_date ?? null;
    }

    public function customer(): array
    {
        return $this->customer ?? [];
    }

    public function vendor(): array
    {
        return $this->vendor ?? [];
    }

    public function items(): array
    {
        return $this->items ?? [];
    }

    public function subtotal(): int|float
    {
        return $this->subtotal ?? 0;
    }

    public function tax(): int|float
    {
        return $this->tax ?? 0;
    }

    public function total(): int|float
    {
        return $this->total ?? ($this->amount ?? 0);
    }

    public function currency(): string
    {
        return $this->currency ?? 'ILS';
    }

    public function notes(): ?string
    {
        return $this->notes ?? null;
    }

    public function terms(): ?string
    {
        return $this->terms ?? null;
    }

    public function formatAmount(int|float $amount): string
    {
        return number_format($amount / 100, 2).' '.$this->currency();
    }

    /**
     * Download the invoice as PDF.
     */
    public function download(): string
    {
        // This would integrate with Pelecard's invoice API
        return '';
    }

    /**
     * View the invoice in browser.
     */
    public function view(): string
    {
        // This would generate HTML view of invoice
        return '';
    }
}
