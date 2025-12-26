<?php

namespace Yousefkadah\Pelecard\Exceptions;

class ValidationException extends PelecardException
{
    protected array $errors = [];

    /**
     * Set validation errors.
     */
    public function setErrors(array $errors): static
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get validation errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Create exception for missing required field.
     */
    public static function missingField(string $field): static
    {
        return (new static("Required field '{$field}' is missing.", 422))
            ->setErrors([$field => ["The {$field} field is required."]]);
    }

    /**
     * Create exception for invalid field value.
     */
    public static function invalidField(string $field, string $reason): static
    {
        return (new static("Invalid value for field '{$field}': {$reason}", 422))
            ->setErrors([$field => [$reason]]);
    }
}
