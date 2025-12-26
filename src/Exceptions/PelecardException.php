<?php

namespace Yousefkadah\Pelecard\Exceptions;

use Exception;

class PelecardException extends Exception
{
    /**
     * Create a new Pelecard exception instance.
     */
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create exception from API response.
     */
    public static function fromResponse(array $response): static
    {
        $message = $response['error_message'] ?? $response['message'] ?? 'Unknown Pelecard error';
        $code = $response['error_code'] ?? $response['code'] ?? 0;

        return new static($message, (int) $code);
    }
}
