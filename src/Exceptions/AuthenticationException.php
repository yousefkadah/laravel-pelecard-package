<?php

namespace Yousefkadah\Pelecard\Exceptions;

class AuthenticationException extends PelecardException
{
    /**
     * Create a new authentication exception.
     */
    public static function invalidCredentials(): static
    {
        return new static('Invalid Pelecard API credentials provided.', 401);
    }

    /**
     * Create exception for missing credentials.
     */
    public static function missingCredentials(): static
    {
        return new static('Pelecard API credentials are missing. Please configure your credentials.', 401);
    }
}
