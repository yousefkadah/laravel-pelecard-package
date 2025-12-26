<?php

namespace Yousefkadah\Pelecard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Crypt;

class PelecardCredentials extends Model
{
    protected $table = 'pelecard_credentials';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'terminal',
        'user',
        'password',
        'environment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the owner of the credentials (polymorphic).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the terminal number.
     */
    public function getTerminal(): string
    {
        return $this->terminal;
    }

    /**
     * Get the API user.
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Get the decrypted password.
     */
    public function getPassword(): string
    {
        try {
            return Crypt::decryptString($this->password);
        } catch (\Exception) {
            // If decryption fails, assume it's already plain text (for backward compatibility)
            return $this->password;
        }
    }

    /**
     * Set the encrypted password.
     */
    public function setPasswordAttribute(string $value): void
    {
        // Only encrypt if not already encrypted
        if (! str_starts_with($value, 'eyJpdiI6')) {
            $this->attributes['password'] = Crypt::encryptString($value);
        } else {
            $this->attributes['password'] = $value;
        }
    }

    /**
     * Check if credentials are active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if sandbox environment.
     */
    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    /**
     * Check if production environment.
     */
    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    /**
     * Scope to get only active credentials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get sandbox credentials.
     */
    public function scopeSandbox($query)
    {
        return $query->where('environment', 'sandbox');
    }

    /**
     * Scope to get production credentials.
     */
    public function scopeProduction($query)
    {
        return $query->where('environment', 'production');
    }
}
