<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Yousefkadah\Pelecard\Subscription;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    public Subscription $subscription;

    /**
     * Create a new event instance.
     */
    public function __construct(Subscription $subscription)
    {
        $this->subscription = $subscription;
    }
}
