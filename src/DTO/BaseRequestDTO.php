<?php

namespace Yousefkadah\Pelecard\DTO;

use Yousefkadah\Pelecard\Exceptions\ValidationException;

abstract class BaseRequestDTO
{
    /**
     * Custom parameter for tracking (returned in callbacks).
     */
    public ?string $paramX = null;

    /**
     * Additional custom parameter for tracking (returned in callbacks).
     */
    public ?string $paramZ = null;

    /**
     * Convert DTO to array format for Pelecard API.
     */
    abstract public function toArray(): array;

    /**
     * Get required fields for validation.
     */
    abstract public function getRequiredFields(): array;

    /**
     * Validate the DTO.
     */
    public function validate(): void
    {
        foreach ($this->getRequiredFields() as $field) {
            if (empty($this->$field)) {
                throw ValidationException::missingField($field);
            }
        }
    }

    /**
     * Add common parameters to array.
     */
    protected function addCommonParams(array $data): array
    {
        if ($this->paramX !== null) {
            $data['param_x'] = $this->paramX;
        }

        if ($this->paramZ !== null) {
            $data['param_z'] = $this->paramZ;
        }

        return $data;
    }
}
