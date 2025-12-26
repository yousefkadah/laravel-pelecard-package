<?php

namespace Yousefkadah\Pelecard;

use Carbon\Carbon;

class SubscriptionBuilder
{
    protected int $quantity = 1;
    protected ?Carbon $trialEndsAt = null;
    protected bool $skipTrial = false;
    protected array $items = [];

    /**
     * Create a new subscription builder instance.
     */
    public function __construct(protected \Illuminate\Database\Eloquent\Model $owner, protected string $name, protected string $plan) {}

    /**
     * Set the trial period in days.
     */
    public function trialDays(int $days): static
    {
        $this->trialEndsAt = Carbon::now()->addDays($days);

        return $this;
    }

    /**
     * Set the trial end date.
     */
    public function trialUntil(Carbon $date): static
    {
        $this->trialEndsAt = $date;

        return $this;
    }

    /**
     * Skip the trial period.
     */
    public function skipTrial(): static
    {
        $this->skipTrial = true;
        $this->trialEndsAt = null;

        return $this;
    }

    /**
     * Set the subscription quantity.
     */
    public function quantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Add an additional plan to the subscription.
     */
    public function add(string $plan, int $quantity = 1): static
    {
        $this->items[] = [
            'plan' => $plan,
            'quantity' => $quantity,
        ];

        return $this;
    }

    /**
     * Create the subscription.
     */
    public function create(?string $paymentMethod = null, array $options = []): Subscription
    {
        // Determine trial end date
        $trialEndsAt = $this->skipTrial ? null : $this->trialEndsAt;

        // If owner has generic trial, use it
        if (! $this->skipTrial && ! $trialEndsAt && method_exists($this->owner, 'onGenericTrial') && $this->owner->onGenericTrial()) {
            $trialEndsAt = $this->owner->trial_ends_at;
        }

        // Create the subscription record
        $subscription = $this->owner->subscriptions()->create([
            'name' => $this->name,
            'pelecard_plan' => $this->plan,
            'quantity' => $this->quantity,
            'trial_ends_at' => $trialEndsAt,
        ]);

        // Create subscription items if any
        foreach ($this->items as $item) {
            $subscription->items()->create([
                'pelecard_plan' => $item['plan'],
                'quantity' => $item['quantity'],
            ]);
        }

        // If payment method provided and not on trial, charge immediately
        // This would integrate with Pelecard to set up recurring billing
        // For now, we'll just store the payment method
        if ($paymentMethod && ! $trialEndsAt && method_exists($this->owner, 'updateDefaultPaymentMethod')) {
            $this->owner->updateDefaultPaymentMethod($paymentMethod);
        }

        event(new Events\SubscriptionCreated($subscription));

        return $subscription;
    }
}
