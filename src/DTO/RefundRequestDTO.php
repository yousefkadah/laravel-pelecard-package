<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class RefundRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public string $transactionId,
        public ?int $amount = null,
        public ?string $currency = null,
        public ?string $reason = null,
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'pelecard_transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function validate(): void
    {
        if ($this->amount !== null && $this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }
    }

    public function getRequiredFields(): array
    {
        return ['pelecard_transaction_id'];
    }
}
