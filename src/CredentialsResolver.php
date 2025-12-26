<?php

namespace Yousefkadah\Pelecard;

use Illuminate\Support\Facades\Cache;
use Yousefkadah\Pelecard\Exceptions\AuthenticationException;

class CredentialsResolver
{
    /**
     * Resolve credentials for a billable entity.
     */
    public function resolve(mixed $billable): PelecardCredentials
    {
        // Check cache first
        if (config('pelecard.cache.enabled')) {
            $cacheKey = $this->getCacheKey($billable);
            $cached = Cache::get($cacheKey);

            if ($cached instanceof PelecardCredentials) {
                return $cached;
            }
        }

        // Use custom resolver if defined
        if ($resolver = config('pelecard.credentials_resolver')) {
            $credentials = $resolver($billable);

            if ($credentials instanceof PelecardCredentials) {
                $this->cache($billable, $credentials);

                return $credentials;
            }
        }

        // Try to get credentials from billable entity
        if (method_exists($billable, 'pelecardCredentials')) {
            $credentials = $billable->pelecardCredentials();

            // Handle relationship vs direct return
            if ($credentials instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                $credentials = $credentials->active()->first();
            }

            if ($credentials instanceof PelecardCredentials) {
                $this->cache($billable, $credentials);

                return $credentials;
            }
        }

        // Try polymorphic relationship
        $credentials = PelecardCredentials::where('owner_type', $billable::class)
            ->where('owner_id', $billable->getKey())
            ->active()
            ->first();

        if ($credentials) {
            $this->cache($billable, $credentials);

            return $credentials;
        }

        // Fallback to config credentials
        return $this->resolveFromConfig();
    }

    /**
     * Resolve credentials from config.
     */
    public function resolveFromConfig(): PelecardCredentials
    {
        $terminal = config('pelecard.terminal');
        $user = config('pelecard.user');
        $password = config('pelecard.password');
        $environment = config('pelecard.environment', 'sandbox');

        if (! $terminal || ! $user || ! $password) {
            throw AuthenticationException::missingCredentials();
        }

        // Create a temporary credentials instance (not saved to database)
        return new PelecardCredentials([
            'terminal' => $terminal,
            'user' => $user,
            'password' => $password,
            'environment' => $environment,
            'is_active' => true,
        ]);
    }

    /**
     * Cache credentials for a billable entity.
     */
    public function cache(mixed $billable, PelecardCredentials $credentials): void
    {
        if (! config('pelecard.cache.enabled')) {
            return;
        }

        $cacheKey = $this->getCacheKey($billable);
        $ttl = config('pelecard.cache.ttl', 3600);

        Cache::put($cacheKey, $credentials, $ttl);
    }

    /**
     * Clear cached credentials for a billable entity.
     */
    public function clearCache(mixed $billable): void
    {
        $cacheKey = $this->getCacheKey($billable);
        Cache::forget($cacheKey);
    }

    /**
     * Get cache key for a billable entity.
     */
    protected function getCacheKey(mixed $billable): string
    {
        $prefix = config('pelecard.cache.prefix', 'pelecard');
        $class = str_replace('\\', '_', $billable::class);
        $id = $billable->getKey();

        return "{$prefix}_credentials_{$class}_{$id}";
    }
}
