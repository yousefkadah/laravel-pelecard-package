<?php

namespace Yousefkadah\Pelecard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $pelecard_transaction_id
 * @property string $type
 * @property int $amount
 * @property string $currency
 * @property string $status
 * @property array $metadata
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class PelecardTransaction extends Model
{
    protected $table = 'pelecard_transactions';

    protected $fillable = [
        'user_id',
        'pelecard_transaction_id',
        'type',
        'amount',
        'currency',
        'status',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'));
    }

    /**
     * Check if the transaction was successful.
     */
    public function successful(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the transaction failed.
     */
    public function failed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Scope to get successful transactions.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get failed transactions.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get transactions of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
