<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class AuthorizeRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public int $amount,
        public string $currency,
        public string $cardNumber,
        public string $expiryMonth,
        public string $expiryYear,
        public string $cvv,
        public ?string $cardHolderName = null,
        public ?string $cardHolderId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'currency' => $this->currency,
            'card_number' => $this->cardNumber,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'cvv' => $this->cvv,
            'card_holder_name' => $this->cardHolderName,
            'card_holder_id' => $this->cardHolderId,
            'email' => $this->email,
            'phone' => $this->phone,
            'metadata' => $this->metadata,
        ], fn ($value): bool => $value !== null);
    }

    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }

        if (strlen($this->expiryMonth) !== 2) {
            throw ValidationException::invalidField('expiry_month', 'Expiry month must be 2 digits');
        }

        if (strlen($this->expiryYear) !== 4) {
            throw ValidationException::invalidField('expiry_year', 'Expiry year must be 4 digits');
        }
    }

    public function getRequiredFields(): array
    {
        return ['amount', 'currency', 'card_number', 'expiry_month', 'expiry_year', 'cvv'];
    }
}
