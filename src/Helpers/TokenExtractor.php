<?php

namespace Yousefkadah\Pelecard\Helpers;

use Yousefkadah\Pelecard\Http\Response;

class TokenExtractor
{
    /**
     * Extract token from payment response.
     * Works with J2 (Debit), J4 (Authorize), and J5 (Authorize with payments) responses.
     */
    public static function extractToken(Response $response): ?string
    {
        if (! $response->isSuccessful()) {
            return null;
        }

        // Try different token field names used by Pelecard
        $tokenFields = ['Token', 'token', 'CreditCardToken', 'CardToken'];

        foreach ($tokenFields as $field) {
            $token = $response->get($field);
            if ($token) {
                return $token;
            }
        }

        return null;
    }

    /**
     * Extract card details from payment response.
     */
    public static function extractCardDetails(Response $response): array
    {
        return [
            'last_four' => $response->get('Last4Digits') ?? $response->get('CreditCardNumber4Digits') ?? null,
            'brand' => $response->get('CardBrand') ?? $response->get('CreditCardCompany') ?? 'unknown',
            'exp_month' => $response->get('ExpiryMonth') ?? null,
            'exp_year' => $response->get('ExpiryYear') ?? null,
            'card_holder' => $response->get('CardHolderName') ?? null,
        ];
    }

    /**
     * Check if response contains a token.
     */
    public static function hasToken(Response $response): bool
    {
        return self::extractToken($response) !== null;
    }
}
