<?php

namespace Yousefkadah\Pelecard\DTO;

class TransactionQueryDTO extends BaseRequestDTO
{
    public function __construct(
        public ?string $transactionId = null,
        public ?string $uniqueId = null,
        public ?string $uid = null,
        public ?string $fromDate = null,
        public ?string $toDate = null,
        public ?array $filters = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'pelecard_transaction_id' => $this->transactionId,
            'unique_id' => $this->uniqueId,
            'uid' => $this->uid,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'filters' => $this->filters,
        ], fn($value) => $value !== null);
    }

    public function validate(): void
    {
        // At least one identifier should be provided
        if (!$this->transactionId && !$this->uniqueId && !$this->uid && !$this->fromDate) {
            throw new \InvalidArgumentException('At least one identifier or date filter is required');
        }
    }

    public function getRequiredFields(): array
    {
        return [];
    }
}
