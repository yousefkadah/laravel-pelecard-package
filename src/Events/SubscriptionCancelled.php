<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Yousefkadah\Pelecard\Subscription;

class SubscriptionCancelled
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Subscription $subscription) {}
}
