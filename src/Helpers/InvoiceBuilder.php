<?php

namespace Yousefkadah\Pelecard\Helpers;

use Illuminate\Support\Facades\View;
use Yousefkadah\Pelecard\Invoice;

class InvoiceBuilder
{
    protected array $data = [];
    protected ?string $template = null;

    /**
     * Set invoice number.
     */
    public function number(string $number): self
    {
        $this->data['invoice_number'] = $number;
        return $this;
    }

    /**
     * Set invoice date.
     */
    public function date(string|\DateTime $date): self
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }
        $this->data['invoice_date'] = $date;
        return $this;
    }

    /**
     * Set due date.
     */
    public function dueDate(string|\DateTime $date): self
    {
        if ($date instanceof \DateTime) {
            $date = $date->format('Y-m-d');
        }
        $this->data['due_date'] = $date;
        return $this;
    }

    /**
     * Set customer information.
     */
    public function customer(array $customer): self
    {
        $this->data['customer'] = $customer;
        return $this;
    }

    /**
     * Set vendor/company information.
     */
    public function vendor(array $vendor): self
    {
        $this->data['vendor'] = $vendor;
        return $this;
    }

    /**
     * Add invoice items.
     */
    public function items(array $items): self
    {
        $this->data['items'] = $items;
        return $this;
    }

    /**
     * Add a single item.
     */
    public function addItem(string $description, int $quantity, int $unitPrice, ?int $tax = null): self
    {
        if (!isset($this->data['items'])) {
            $this->data['items'] = [];
        }

        $this->data['items'][] = [
            'description' => $description,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax' => $tax,
            'total' => $quantity * $unitPrice,
        ];

        return $this;
    }

    /**
     * Set tax rate.
     */
    public function taxRate(float $rate): self
    {
        $this->data['tax_rate'] = $rate;
        return $this;
    }

    /**
     * Set notes.
     */
    public function notes(string $notes): self
    {
        $this->data['notes'] = $notes;
        return $this;
    }

    /**
     * Set payment terms.
     */
    public function terms(string $terms): self
    {
        $this->data['terms'] = $terms;
        return $this;
    }

    /**
     * Set custom template.
     */
    public function template(string $template): self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Set currency.
     */
    public function currency(string $currency): self
    {
        $this->data['currency'] = $currency;
        return $this;
    }

    /**
     * Calculate totals.
     */
    protected function calculateTotals(): array
    {
        $subtotal = 0;
        $taxAmount = 0;

        foreach ($this->data['items'] ?? [] as $item) {
            $subtotal += $item['total'];
            if (isset($item['tax'])) {
                $taxAmount += $item['tax'];
            }
        }

        // Apply tax rate if set
        if (isset($this->data['tax_rate']) && $this->data['tax_rate'] > 0) {
            $taxAmount = $subtotal * ($this->data['tax_rate'] / 100);
        }

        return [
            'subtotal' => $subtotal,
            'tax' => $taxAmount,
            'total' => $subtotal + $taxAmount,
        ];
    }

    /**
     * Build the invoice.
     */
    public function build(): Invoice
    {
        $totals = $this->calculateTotals();
        
        return new Invoice(array_merge($this->data, $totals));
    }

    /**
     * Render invoice as HTML.
     */
    public function render(): string
    {
        $invoice = $this->build();
        $template = $this->template ?? 'pelecard::invoices.default';

        return View::make($template, ['invoice' => $invoice])->render();
    }

    /**
     * Download invoice as PDF.
     */
    public function pdf(): mixed
    {
        // This would integrate with a PDF library like dompdf or snappy
        // For now, return HTML that can be converted to PDF
        return $this->render();
    }

    /**
     * Send invoice via email.
     */
    public function send(string $email, ?string $subject = null): bool
    {
        // This would integrate with Laravel's mail system
        // For now, just a placeholder
        return true;
    }

    /**
     * Get invoice data for Inertia.js.
     * Returns structured data that can be used in Vue/React components.
     */
    public function toInertia(): array
    {
        $invoice = $this->build();
        
        return [
            'invoice' => [
                'number' => $invoice->number(),
                'date' => $invoice->date(),
                'dueDate' => $invoice->dueDate(),
                'customer' => $invoice->customer(),
                'vendor' => $invoice->vendor(),
                'items' => $invoice->items(),
                'subtotal' => $invoice->subtotal(),
                'tax' => $invoice->tax(),
                'total' => $invoice->total(),
                'currency' => $invoice->currency(),
                'notes' => $invoice->notes(),
                'terms' => $invoice->terms(),
                'formatted' => [
                    'subtotal' => $invoice->formatAmount($invoice->subtotal()),
                    'tax' => $invoice->formatAmount($invoice->tax()),
                    'total' => $invoice->formatAmount($invoice->total()),
                ],
            ],
        ];
    }
}
