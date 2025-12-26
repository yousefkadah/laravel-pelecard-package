<?php

namespace Yousefkadah\Pelecard\Http;

use Yousefkadah\Pelecard\Exceptions\PaymentException;
use Yousefkadah\Pelecard\Exceptions\PelecardException;

class Response
{
    protected array $data;
    protected int $statusCode;
    protected ?string $rawBody;

    /**
     * Create a new response instance.
     */
    public function __construct(array $data, int $statusCode = 200, ?string $rawBody = null)
    {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->rawBody = $rawBody;
    }

    /**
     * Check if the response was successful.
     */
    public function successful(): bool
    {
        // Pelecard uses StatusCode field in response
        if (isset($this->data['StatusCode'])) {
            return $this->data['StatusCode'] === '000' || $this->data['StatusCode'] === 0;
        }

        // Fallback to HTTP status code
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the response failed.
     */
    public function failed(): bool
    {
        return ! $this->successful();
    }

    /**
     * Get response data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific field from response.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get transaction ID from response.
     */
    public function getTransactionId(): ?string
    {
        return $this->get('PelecardTransactionId') 
            ?? $this->get('TransactionId')
            ?? $this->get('transaction_id');
    }

    /**
     * Get authorization number.
     */
    public function getAuthorizationNumber(): ?string
    {
        return $this->get('AuthorizationNumber') 
            ?? $this->get('ConfirmationNumber')
            ?? $this->get('authorization_number');
    }

    /**
     * Get error message.
     */
    public function getErrorMessage(): ?string
    {
        return $this->get('ErrorMessage') 
            ?? $this->get('Error')
            ?? $this->get('error_message')
            ?? $this->get('message');
    }

    /**
     * Get error code.
     */
    public function getErrorCode(): ?string
    {
        return $this->get('StatusCode') 
            ?? $this->get('ErrorCode')
            ?? $this->get('error_code');
    }

    /**
     * Get HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get raw response body.
     */
    public function getRawBody(): ?string
    {
        return $this->rawBody;
    }

    /**
     * Throw exception if response failed.
     */
    public function throw(): static
    {
        if ($this->failed()) {
            $message = $this->getErrorMessage() ?? 'Unknown error occurred';
            $code = $this->getErrorCode() ?? '999';

            throw (new PaymentException($message, (int) $code))
                ->setDetails($this->data);
        }

        return $this;
    }

    /**
     * Throw exception if response failed, otherwise return data.
     */
    public function throwOrReturn(): array
    {
        return $this->throw()->getData();
    }

    /**
     * Convert response to array.
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Convert response to JSON.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->data, $options);
    }
}
