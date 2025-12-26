<?php

namespace Yousefkadah\Pelecard;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $pelecard_subscription_id
 * @property string $pelecard_plan
 * @property int $quantity
 * @property \Carbon\Carbon|null $trial_ends_at
 * @property \Carbon\Carbon|null $ends_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Subscription extends Model
{
    protected $table = 'subscriptions';

    protected $fillable = [
        'user_id',
        'name',
        'pelecard_subscription_id',
        'pelecard_plan',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Get the subscription items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SubscriptionItem::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function active(): bool
    {
        return $this->valid();
    }

    /**
     * Check if the subscription is valid.
     */
    public function valid(): bool
    {
        if ($this->onTrial()) {
            return true;
        }

        return $this->recurring() && ! $this->cancelled();
    }

    /**
     * Check if the subscription is recurring.
     */
    public function recurring(): bool
    {
        return ! $this->onTrial() && ! $this->cancelled();
    }

    /**
     * Check if the subscription is cancelled.
     */
    public function cancelled(): bool
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Check if the subscription is on trial.
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is on grace period.
     */
    public function onGracePeriod(): bool
    {
        return $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Check if the subscription has a specific plan.
     */
    public function hasPlan(string $plan): bool
    {
        return $this->pelecard_plan === $plan;
    }

    /**
     * Cancel the subscription at the end of the billing period.
     */
    public function cancel(): static
    {
        $this->ends_at = $this->onTrial()
            ? $this->trial_ends_at
            : Carbon::now()->addMonth();

        $this->save();

        event(new Events\SubscriptionCancelled($this));

        return $this;
    }

    /**
     * Cancel the subscription immediately.
     */
    public function cancelNow(): static
    {
        $this->ends_at = Carbon::now();
        $this->save();

        event(new Events\SubscriptionCancelled($this));

        return $this;
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resume(): static
    {
        if (! $this->onGracePeriod()) {
            throw new \LogicException('Cannot resume a subscription that is not on grace period.');
        }

        $this->ends_at = null;
        $this->save();

        event(new Events\SubscriptionUpdated($this));

        return $this;
    }

    /**
     * Swap the subscription to a new plan.
     */
    public function swap(string $plan): static
    {
        $oldPlan = $this->pelecard_plan;

        $this->pelecard_plan = $plan;
        $this->save();

        event(new Events\SubscriptionUpdated($this, $oldPlan));

        return $this;
    }

    /**
     * Swap the subscription to a new plan without proration.
     */
    public function swapWithoutProration(string $plan): static
    {
        return $this->noProrate()->swap($plan);
    }

    /**
     * Increment the quantity of the subscription.
     */
    public function incrementQuantity(int $count = 1): static
    {
        return $this->updateQuantity($this->quantity + $count);
    }

    /**
     * Decrement the quantity of the subscription.
     */
    public function decrementQuantity(int $count = 1): static
    {
        return $this->updateQuantity(max(1, $this->quantity - $count));
    }

    /**
     * Update the quantity of the subscription.
     */
    public function updateQuantity(int $quantity): static
    {
        $this->quantity = $quantity;
        $this->save();

        event(new Events\SubscriptionUpdated($this));

        return $this;
    }

    /**
     * Disable proration for the next operation.
     */
    public function noProrate(): static
    {
        // This would be used to prevent proration on next swap
        // Implementation depends on Pelecard's proration support
        return $this;
    }

    /**
     * Skip the trial period.
     */
    public function skipTrial(): static
    {
        $this->trial_ends_at = null;
        $this->save();

        return $this;
    }

    /**
     * Scope to get active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query): void {
            $query->whereNull('ends_at')
                ->orWhere('ends_at', '>', Carbon::now());
        });
    }

    /**
     * Scope to get cancelled subscriptions.
     */
    public function scopeCancelled($query)
    {
        return $query->whereNotNull('ends_at');
    }

    /**
     * Scope to get subscriptions on trial.
     */
    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', Carbon::now());
    }

    /**
     * Scope to get subscriptions on grace period.
     */
    public function scopeOnGracePeriod($query)
    {
        return $query->whereNotNull('ends_at')
            ->where('ends_at', '>', Carbon::now());
    }
}
