<?php

namespace Yousefkadah\Pelecard;

class PaymentMethod
{
    public ?string $token = null;
    public ?string $type = null;
    public ?string $last_four = null;
    public ?string $brand = null;
    public ?string $expiry_month = null;
    public ?string $expiry_year = null;

    /**
     * Create a new payment method instance.
     */
    public function __construct(array $attributes = [])
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get the payment method as an array.
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'type' => $this->type,
            'last_four' => $this->last_four,
            'brand' => $this->brand,
            'expiry_month' => $this->expiry_month,
            'expiry_year' => $this->expiry_year,
        ];
    }

    /**
     * Check if the payment method is expired.
     */
    public function isExpired(): bool
    {
        if (! $this->expiry_month || ! $this->expiry_year) {
            return false;
        }

        $expiryDate = \Carbon\Carbon::createFromDate((int) $this->expiry_year, (int) $this->expiry_month, 1)->endOfMonth();

        return $expiryDate->isPast();
    }
}
