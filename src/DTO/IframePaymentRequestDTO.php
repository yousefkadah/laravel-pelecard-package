<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class IframePaymentRequestDTO extends BaseRequestDTO
{
    public function __construct(
        public int $amount,
        public string $currency,
        public string $successUrl,
        public string $errorUrl,
        public string $cancelUrl,
        public ?string $language = 'he',
        public ?string $paramX = null,
        public ?string $topText = null,
        public ?string $bottomText = null,
        public ?string $logoUrl = null,
        public ?bool $hidePelecardLogo = false,
        public ?bool $showConfirmation = true,
        public ?int $minPayments = 1,
        public ?int $maxPayments = 1,
        public ?string $customerName = null,
        public ?string $email = null,
        public ?string $phone = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'currency' => $this->currency,
            'success_url' => $this->successUrl,
            'error_url' => $this->errorUrl,
            'cancel_url' => $this->cancelUrl,
            'language' => $this->language,
            'param_x' => $this->paramX,
            'top_text' => $this->topText,
            'bottom_text' => $this->bottomText,
            'logo_url' => $this->logoUrl,
            'hide_pelecard_logo' => $this->hidePelecardLogo ? '1' : '0',
            'show_confirmation' => $this->showConfirmation ? '1' : '0',
            'min_payments' => $this->minPayments,
            'max_payments' => $this->maxPayments,
            'customer_name' => $this->customerName,
            'email' => $this->email,
            'phone' => $this->phone,
        ], fn($value) => $value !== null);
    }

    public function validate(): void
    {
        if ($this->amount <= 0) {
            throw ValidationException::invalidField('amount', 'Amount must be greater than 0');
        }

        if ($this->minPayments > $this->maxPayments) {
            throw ValidationException::invalidField('min_payments', 'Min payments cannot be greater than max payments');
        }
    }

    public function getRequiredFields(): array
    {
        return ['amount', 'currency', 'success_url', 'error_url', 'cancel_url'];
    }
}
