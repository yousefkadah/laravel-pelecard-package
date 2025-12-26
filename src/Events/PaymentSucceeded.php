<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Yousefkadah\Pelecard\Http\Response;

class PaymentSucceeded
{
    use Dispatchable, SerializesModels;

    public Response $response;
    public $billable;

    /**
     * Create a new event instance.
     */
    public function __construct(Response $response, $billable = null)
    {
        $this->response = $response;
        $this->billable = $billable;
    }
}
