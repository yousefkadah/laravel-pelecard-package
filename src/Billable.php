<?php

namespace Yousefkadah\Pelecard;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Yousefkadah\Pelecard\Events\PaymentFailed;
use Yousefkadah\Pelecard\Events\PaymentSucceeded;
use Yousefkadah\Pelecard\Http\Response;

trait Billable
{
    /**
     * Get all subscriptions for the billable entity.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, $this->getForeignKey())->orderBy('created_at', 'desc');
    }

    /**
     * Get a subscription by name.
     */
    public function subscription(string $name = 'default'): ?Subscription
    {
        return $this->subscriptions()->where('name', $name)->first();
    }

    /**
     * Check if the billable entity is subscribed to a plan.
     */
    public function subscribed(string $name = 'default', ?string $plan = null): bool
    {
        $subscription = $this->subscription($name);

        if (! $subscription || ! $subscription->valid()) {
            return false;
        }

        if ($plan) {
            return $subscription->hasPlan($plan);
        }

        return true;
    }

    /**
     * Check if the billable entity is on trial.
     */
    public function onTrial(string $name = 'default'): bool
    {
        $subscription = $this->subscription($name);

        return $subscription && $subscription->onTrial();
    }

    /**
     * Check if the billable entity is on a generic trial.
     */
    public function onGenericTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Create a new subscription.
     */
    public function newSubscription(string $name, string $plan): SubscriptionBuilder
    {
        return new SubscriptionBuilder($this, $name, $plan);
    }

    /**
     * Get Pelecard credentials for this billable entity.
     */
    public function pelecardCredentials(): ?PelecardCredentials
    {
        if (config('pelecard.multi_tenant')) {
            $resolver = app(CredentialsResolver::class);

            return $resolver->resolve($this);
        }

        return null;
    }

    /**
     * Get the Pelecard client for this billable entity.
     */
    public function pelecardClient(): PelecardClient
    {
        return PelecardClient::for($this);
    }

    /**
     * Charge the billable entity and return the response.
     */
    public function charge(int $amount, string $description, array $options = []): Response
    {
        $client = PelecardClient::for($this);

        $data = array_merge([
            'amount' => $amount,
            'description' => $description,
            'currency' => config('pelecard.currency', 'ILS'),
        ], $options);

        $response = $client->charge($data);

        // Log the transaction
        $this->pelecardTransactions()->create([
            'type' => 'charge',
            'amount' => $amount,
            'currency' => $data['currency'],
            'pelecard_transaction_id' => $response->getTransactionId(),
            'status' => $response->isSuccessful() ? 'successful' : 'failed',
            'response' => $response->toArray(),
        ]);

        if ($response->isSuccessful()) {
            event(new PaymentSucceeded($this, $response));
        } else {
            event(new PaymentFailed($this, $response));
        }

        return $response;
    }

    /**
     * Charge the billable entity and automatically save the card token.
     * This method extracts the token from the payment response (J2/J4/J5)
     * and saves it as the default payment method.
     */
    public function chargeAndSaveCard(int $amount, string $description, array $cardDetails = []): Response
    {
        $client = PelecardClient::for($this);

        $data = array_merge([
            'amount' => $amount,
            'description' => $description,
            'currency' => config('pelecard.currency', 'ILS'),
        ], $cardDetails);

        // Make the payment (J2/J4/J5 will return a token)
        $response = $client->charge($data);

        // Log the transaction
        $this->pelecardTransactions()->create([
            'type' => 'charge',
            'amount' => $amount,
            'currency' => $data['currency'],
            'pelecard_transaction_id' => $response->getTransactionId(),
            'status' => $response->isSuccessful() ? 'successful' : 'failed',
            'response' => $response->toArray(),
        ]);

        // If successful, extract and save the token
        if ($response->isSuccessful()) {
            $token = $response->get('Token');

            if ($token) {
                $this->updateDefaultPaymentMethod($token, [
                    'type' => 'card',
                    'last_four' => $response->get('Last4Digits') ?? substr($cardDetails['card_number'] ?? '', -4),
                    'brand' => $response->get('CardBrand') ?? 'unknown',
                    'exp_month' => $cardDetails['expiry_month'] ?? null,
                    'exp_year' => $cardDetails['expiry_year'] ?? null,
                ]);
            }

            event(new PaymentSucceeded($this, $response));
        } else {
            event(new PaymentFailed($this, $response));
        }

        return $response;
    }

    /**
     * Refund a transaction.
     */
    public function refund(string $transactionId, int $amount): Http\Response
    {
        $client = $this->pelecardClient();
        $response = $client->refund($transactionId, $amount);

        $this->logTransaction('refund', $response);

        return $response;
    }

    /**
     * Update the default payment method from a token.
     */
    public function updateDefaultPaymentMethod(string $token, array $cardDetails = []): void
    {
        $this->forceFill([
            'pelecard_token' => $token,
            'card_brand' => $cardDetails['brand'] ?? null,
            'card_last_four' => $cardDetails['last_four'] ?? null,
            'card_exp_month' => $cardDetails['exp_month'] ?? null,
            'card_exp_year' => $cardDetails['exp_year'] ?? null,
        ])->save();

        event(new \Yousefkadah\Pelecard\Events\CardSaved($this, $token, $cardDetails));
    }

    /**
     * Update the default payment method from a payment response.
     * Automatically extracts token and card details from the response.
     */
    public function updateDefaultPaymentMethodFromResponse(\Yousefkadah\Pelecard\Http\Response $response): bool
    {
        $token = \Yousefkadah\Pelecard\Helpers\TokenExtractor::extractToken($response);

        if (! $token) {
            return false;
        }

        $cardDetails = \Yousefkadah\Pelecard\Helpers\TokenExtractor::extractCardDetails($response);
        $this->updateDefaultPaymentMethod($token, $cardDetails);

        return true;
    }

    /**
     * Get the default payment method.
     */
    public function defaultPaymentMethod(): ?PaymentMethod
    {
        if (! $this->hasDefaultPaymentMethod()) {
            return null;
        }

        // In a real implementation, you'd retrieve the token from a secure storage
        // For now, we'll return a basic PaymentMethod object
        return new PaymentMethod([
            'type' => $this->pm_type,
            'last_four' => $this->pm_last_four,
        ]);
    }

    /**
     * Check if the billable entity has a default payment method.
     */
    public function hasDefaultPaymentMethod(): bool
    {
        return ! is_null($this->pm_type);
    }

    /**
     * Delete the default payment method.
     */
    public function deletePaymentMethod(): void
    {
        $this->forceFill([
            'pm_type' => null,
            'pm_last_four' => null,
        ])->save();
    }

    /**
     * Get all invoices for the billable entity.
     */
    public function invoices(): array
    {
        // This would integrate with Pelecard's invoice API
        // For now, return empty array
        return [];
    }

    /**
     * Find a specific invoice.
     */
    public function findInvoice(string $id): ?Invoice
    {
        // This would retrieve invoice from Pelecard
        return null;
    }

    /**
     * Download an invoice as PDF.
     */
    public function downloadInvoice(string $id, array $data = []): string
    {
        // This would generate/download invoice PDF
        return '';
    }

    /**
     * Log a transaction for this billable entity.
     */
    protected function logTransaction(string $type, Http\Response $response): void
    {
        if (! $response->successful()) {
            return;
        }

        $this->pelecardTransactions()->create([
            'pelecard_transaction_id' => $response->getTransactionId(),
            'type' => $type,
            'amount' => $response->get('Amount') ?? $response->get('amount'),
            'currency' => $response->get('Currency') ?? config('pelecard.currency'),
            'status' => $response->successful() ? 'completed' : 'failed',
            'metadata' => $response->getData(),
        ]);
    }

    /**
     * Get all transactions for this billable entity.
     */
    public function pelecardTransactions(): HasMany
    {
        return $this->hasMany(PelecardTransaction::class, $this->getForeignKey());
    }

    /**
     * Get the billable entity's Pelecard ID.
     */
    public function pelecardId(): ?string
    {
        return $this->pelecard_id;
    }

    /**
     * Set the billable entity's Pelecard ID.
     */
    public function setPelecardId(string $id): void
    {
        $this->pelecard_id = $id;
        $this->save();
    }
}
