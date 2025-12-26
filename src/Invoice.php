<?php

namespace Yousefkadah\Pelecard;

use ArrayAccess;

class Invoice implements ArrayAccess
{
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
