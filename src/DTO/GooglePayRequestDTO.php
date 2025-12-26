<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class GooglePayRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public int $amount,
        public string $currency,
        public string $googlePayToken,
        public ?string $email = null,
        public ?string $phone = null,
        public ?int $payments = 1,
        public ?array $metadata = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'currency' => $this->currency,
            'google_pay_token' => $this->googlePayToken,
            'email' => $this->email,
            'phone' => $this->phone,
            'payments' => $this->payments,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }

    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }

        if (empty($this->googlePayToken)) {
            throw ValidationException::missingField('google_pay_token');
        }
    }

    public function getRequiredFields(): array
    {
        return ['amount', 'currency', 'google_pay_token'];
    }
}
