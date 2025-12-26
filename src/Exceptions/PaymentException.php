<?php

namespace Yousefkadah\Pelecard\Exceptions;

class PaymentException extends PelecardException
{
    protected array $details = [];

    /**
     * Set payment details.
     */
    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get payment details.
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Create exception for declined payment.
     */
    public static function declined(string $reason = 'Payment was declined'): static
    {
        return new static($reason, 402);
    }

    /**
     * Create exception for insufficient funds.
     */
    public static function insufficientFunds(): static
    {
        return new static('Insufficient funds for this transaction.', 402);
    }

    /**
     * Create exception for invalid card.
     */
    public static function invalidCard(string $reason = 'Invalid card details'): static
    {
        return new static($reason, 400);
    }
}
