<?php

namespace Yousefkadah\Pelecard\DTO;

class TokenRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public string $cardNumber,
        public string $expiryMonth,
        public string $expiryYear,
        public ?string $cardHolderName = null,
        public ?string $cardHolderId = null,
        public ?bool $skipValidation = false,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'card_number' => $this->cardNumber,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'card_holder_name' => $this->cardHolderName,
            'card_holder_id' => $this->cardHolderId,
        ], fn ($value): bool => $value !== null);
    }

    public function validate(): void
    {
        // Validation handled by base Request class
    }

    public function getRequiredFields(): array
    {
        return ['card_number', 'expiry_month', 'expiry_year'];
    }
}
