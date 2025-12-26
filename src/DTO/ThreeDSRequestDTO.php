<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class ThreeDSRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public int $amount,
        public string $currency,
        public string $cardNumber,
        public string $expiryMonth,
        public string $expiryYear,
        public ?string $cvv = null,
        public ?string $cardHolderName = null,
        public ?string $email = null,
        public ?string $successUrl = null,
        public ?string $errorUrl = null,
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
            'email' => $this->email,
            'success_url' => $this->successUrl,
            'error_url' => $this->errorUrl,
            'metadata' => $this->metadata,
        ], fn ($value): bool => $value !== null);
    }

    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }
    }

    public function getRequiredFields(): array
    {
        return ['amount', 'currency', 'card_number', 'expiry_month', 'expiry_year'];
    }
}
