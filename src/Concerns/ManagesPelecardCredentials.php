<?php

namespace Yousefkadah\Pelecard\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Yousefkadah\Pelecard\PelecardCredentials;

trait ManagesPelecardCredentials
{
    /**
     * Get all Pelecard credentials for this entity.
     */
    public function pelecardCredentials(): MorphMany
    {
        return $this->morphMany(PelecardCredentials::class, 'owner');
    }

    /**
     * Get active Pelecard credentials.
     */
    public function activePelecardCredentials(): ?PelecardCredentials
    {
        return $this->pelecardCredentials()->active()->first();
    }

    /**
     * Create new Pelecard credentials.
     */
    public function createPelecardCredentials(
        string $terminal,
        string $user,
        string $password,
        string $environment = 'sandbox',
        bool $isActive = true
    ): PelecardCredentials {
        // Deactivate existing credentials if this is set as active
        if ($isActive) {
            $this->pelecardCredentials()->update(['is_active' => false]);
        }

        return $this->pelecardCredentials()->create([
            'terminal' => $terminal,
            'user' => $user,
            'password' => $password,
            'environment' => $environment,
            'is_active' => $isActive,
        ]);
    }

    /**
     * Update Pelecard credentials.
     */
    public function updatePelecardCredentials(
        string $terminal,
        string $user,
        string $password,
        ?string $environment = null
    ): bool {
        $credentials = $this->activePelecardCredentials();

        if (! $credentials) {
            return false;
        }

        return $credentials->update([
            'terminal' => $terminal,
            'user' => $user,
            'password' => $password,
            'environment' => $environment ?? $credentials->environment,
        ]);
    }

    /**
     * Delete Pelecard credentials.
     */
    public function deletePelecardCredentials(): bool
    {
        return $this->pelecardCredentials()->delete();
    }

    /**
     * Check if entity has Pelecard credentials.
     */
    public function hasPelecardCredentials(): bool
    {
        return $this->pelecardCredentials()->exists();
    }

    /**
     * Check if entity has active Pelecard credentials.
     */
    public function hasActivePelecardCredentials(): bool
    {
        return $this->pelecardCredentials()->active()->exists();
    }

    /**
     * Switch to sandbox environment.
     */
    public function switchToSandbox(): bool
    {
        $credentials = $this->activePelecardCredentials();

        if (! $credentials) {
            return false;
        }

        return $credentials->update(['environment' => 'sandbox']);
    }

    /**
     * Switch to production environment.
     */
    public function switchToProduction(): bool
    {
        $credentials = $this->activePelecardCredentials();

        if (! $credentials) {
            return false;
        }

        return $credentials->update(['environment' => 'production']);
    }
}
