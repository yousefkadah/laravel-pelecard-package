<?php

namespace Yousefkadah\Pelecard;

use ArrayAccess;

class Invoice implements ArrayAccess
{
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
