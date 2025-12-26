<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Yousefkadah\Pelecard\Subscription;

class SubscriptionUpdated
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;
    public ?string $oldPlan;

    /**
     * Create a new event instance.
     */
    public function __construct(Subscription $subscription, ?string $oldPlan = null)
    {
        $this->subscription = $subscription;
        $this->oldPlan = $oldPlan;
    }
}
