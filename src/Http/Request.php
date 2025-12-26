<?php

namespace Yousefkadah\Pelecard\Http;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

class Request
{
    protected array $data = [];
    protected array $requiredFields = [];

    /**
     * Create a new request instance.
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Set required fields for validation.
     */
    public function setRequiredFields(array $fields): static
    {
        $this->requiredFields = $fields;

        return $this;
    }

    /**
     * Validate the request data.
     */
    public function validate(): void
    {
        foreach ($this->requiredFields as $field) {
            if (! isset($this->data[$field]) || $this->data[$field] === '') {
                throw ValidationException::missingField($field);
            }
        }
    }

    /**
     * Get request data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set a data field.
     */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Get a data field.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Convert to Pelecard API format.
     */
    public function toPelecardFormat(): array
    {
        // Pelecard uses PascalCase for field names
        $formatted = [];

        foreach ($this->data as $key => $value) {
            $formatted[$this->toPascalCase($key)] = $value;
        }

        return $formatted;
    }

    /**
     * Convert snake_case to PascalCase.
     */
    protected function toPascalCase(string $string): string
    {
        return str_replace('_', '', ucwords($string, '_'));
    }

    /**
     * Create request from array.
     */
    public static function make(array $data): static
    {
        return new static($data);
    }
}
