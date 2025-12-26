<?php

namespace Yousefkadah\Pelecard\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CardSaved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public mixed $billable,
        public string $token,
        public array $cardDetails
    ) {}
}
