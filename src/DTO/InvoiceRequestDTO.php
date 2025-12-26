<?php

namespace Yousefkadah\Pelecard\DTO;

class InvoiceRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public string $customerName,
        public int $amount,
        public string $currency,
        public array $items,
        public ?string $customerEmail = null,
        public ?string $customerPhone = null,
        public ?string $customerAddress = null,
        public ?string $invoiceType = 'icount', // icount, ezcount, payper
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'customer_name' => $this->customerName,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'items' => $this->items,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function validate(): void
    {
        // Validation handled by specific invoice methods
    }

    public function getRequiredFields(): array
    {
        return ['customer_name', 'amount', 'items'];
    }
}
