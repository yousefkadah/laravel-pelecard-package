<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class ChargeRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public int $amount,
        public string $currency,
        public ?string $cardNumber = null,
        public ?string $expiryMonth = null,
        public ?string $expiryYear = null,
        public ?string $cvv = null,
        public ?string $token = null,
        public ?string $cardHolderName = null,
        public ?string $cardHolderId = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?int $payments = 1,
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        $data = [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'card_number' => $this->cardNumber,
            'token' => $this->token,
            'expiry_month' => $this->expiryMonth,
            'expiry_year' => $this->expiryYear,
            'cvv' => $this->cvv,
            'email' => $this->email,
            'customer_name' => $this->cardHolderName, // Assuming cardHolderName maps to customer_name
            'payments' => $this->payments,
            'phone' => $this->phone,
            'card_holder_id' => $this->cardHolderId,
            'metadata' => $this->metadata,
        ];

        return $this->addCommonParams(array_filter($data, fn ($value): bool => $value !== null));
    }

    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }

        // Either card details or token must be provided
        if (! $this->token && ! $this->cardNumber) {
            throw ValidationException::missingField('card_number or token');
        }

        // If using card, all card fields are required
        if ($this->cardNumber && (! $this->expiryMonth || ! $this->expiryYear || ! $this->cvv)) {
            throw ValidationException::missingField('expiry_month, expiry_year, and cvv are required when using card_number');
        }
    }

    public function getRequiredFields(): array
    {
        return ['amount', 'currency'];
    }
}
