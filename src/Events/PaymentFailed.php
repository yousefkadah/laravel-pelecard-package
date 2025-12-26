<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Yousefkadah\Pelecard\Http\Response;

class PaymentFailed
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Response $response, public $billable = null) {}
}
