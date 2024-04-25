<?php

namespace TremulantTech\LaravelWalmartApi\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use TremulantTech\LaravelWalmartApi\Configuration;
use Walmart\Apis\BaseApi;
use Walmart\Walmart;

class Credentials extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'walmart_api_credentials';

    /**
     * The attributes that are mass assignable.
     * Think the client_id === consumer_id and client_secret === private_key
     * and are just named different depending on the auth schema used by the
     * endpoint trying to be consumed. Good job walmart :/.
     *
     * @var array
     */
    protected $fillable = [
        'client_id',
        'client_secret',
        'consumer_id',
        'private_key',
        'channel_type',
        'partner_id',
        'refresh_token',
        'grant_type',
        'country',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * OAuth Access/Authorization Code
     * After a seller has granted authorization to the application to perform
     * actions on their behalf, a access code is returned and exchanged for an
     * access token and refresh token.
     *
     * @var string|null
     */
    protected ?string $access_code = null;

    /**
     * Relatively short lived token.
     *
     * @var string|null
     */
    protected ?string $access_token = null;

    /**
     * Usually a bearer token.
     *
     * @var string|null
     */
    protected ?string $token_type = null;

    /**
     * Expiration time of the access_token.
     *
     * @var Carbon|null
     */
    protected ?Carbon $expires_at = null;

    /**
     * Perform any actions required after the model boots.
     *
     * When eloquent updating events occur, forget the cached access token and
     * expiry. This will force a new fetch of an access token with the updated
     * parameters of the Credentials model.
     * @return void
     */
    protected static function booted(): void
    {
        static::updating(function (self $credentials) {
            $credentials->clearCache()->clearAccessToken();
        });
    }

    /**
     * Clear out the cached access token and expiry.
     *
     * @return static
     */
    public function clearCache(): static
    {
        Cache::forget($this->getAccessTokenCacheKey());

        Cache::forget($this->getExpiresAtCacheKey());

        return $this;
    }

    public function clearAccessToken(): static
    {
        $this->access_token = null;

        $this->expires_at = null;

        return $this;
    }

    /**
     * Get the Seller that owns the Credentials.
     *
     * @return BelongsTo
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Convert this Credentials model to a Configuration instance use by Walmart\Walmart.
     *
     * @param bool $dummy  If true, the returned Configuration will throw an exception if any API
     *  methods are called. This is useful for preventing unauthorized errors as result of auto-injected
     *  API class being used before it has been passed through Credentials::useOn().
     * @return Configuration
     */
    public function toApiConfiguration(bool $dummy = false): Configuration
    {
        $configuration = new Configuration($dummy, [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'consumer_id' => $this->consumer_id,
            'private_key' => $this->private_key,
            'channel_type' => $this->channel_type,
            'partner_id' => $this->partner_id,
            'access_token' => $this->getAccessToken(),
            'expires_at' => $this->getExpiresAt(),
            'country' => $this->country,
        ]);

        if (config('walmart-api.debug', false)) {
            $configuration->setDebug(true);

            $configuration->setDebugFile(config('walmart-api.debug_file'));
        }

        return $configuration;
    }

    /**
     * The BaseApi does not have any way of setting a config except at
     * instantiation. Fetching the config off the api instance returns a
     * static Configuration object that we can just set via public class
     * methods.
     *
     * @param BaseApi $api
     * @return BaseApi
     */
    public function applyTo(BaseApi $api): BaseApi
    {
        $config = $this->toApiConfiguration();

        $apiConfig = $api->getConfig();

        $class = new \ReflectionClass($apiConfig::class);

        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            try {
                $methodName = $method->getName();

                if (str_starts_with($methodName, 'set')) {
                    $getter = str_replace('set', 'get', $methodName);

                    $apiConfig->{$methodName}($config->$getter());
                }
            } catch (\Throwable) {
                //ignore the properties not set
            }
        }

        return $api;
    }

    /**
     * Interact with the client's secret.
     *
     * @return Attribute
     */
    protected function clientSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Interact with the client's private key.
     *
     * @return Attribute
     */
    protected function privateKey(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Retrieve the access token expiration.
     *
     * @return Attribute
     */
    protected function expiresAt(): Attribute
    {
        return Attribute::make(fn () => $this->getExpiresAt());
    }

    /**
     * Interact with the client's access token.
     *
     * @return Attribute
     */
    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($this->getAccessToken()) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Interact with the client's refresh token.
     *
     * @return Attribute
     */
    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? Crypt::decryptString($value) : null,
            set: fn ($value) => $value ? Crypt::encryptString($value) : null,
        );
    }

    /**
     * Attempt to get the access token. Will check $this, then Cache, and
     * then attempt a fetch from Walmart's token endpoint.
     * @return string
     */
    protected function getAccessToken(): string
    {
        if (!is_null($this->access_token)) {
            return $this->access_token;
        }

        $cachedToken = Cache::get($tokenCacheKey = $this->getAccessTokenCacheKey());

        if (!is_null($cachedToken)) {
            $this->access_token = $cachedToken;
        } else {
            $authApi = Walmart::marketplace(
                new Configuration(false, [
                    'client_id' => $this->client_id,
                    'client_secret' => $this->client_secret,
                    'country' => $this->country,
                ])
            )->auth();

            $oAuthToken = $authApi->tokenAPI(
                $this->grant_type,
                $this->access_code,
                config('walmart-api.redirect_url'),
                $this->refresh_token
            );

            $this->refresh_token = $oAuthToken->getRefreshToken();

            /**
             * If we're oAuth'n we need to save the refresh token to the model.
             * This will clear the current access_token on the instance
             * and in the cache.
             */
            $this->save();

            $this->access_token = $oAuthToken->getAccessToken();

            $this->expires_at = Carbon::now()->addSeconds($oAuthToken->getExpiresIn());

            $this->token_type = $oAuthToken->getTokenType();

            Cache::put($tokenCacheKey, $this->access_token, $this->expires_at);

            Cache::put($this->getExpiresAtCacheKey(), $this->expires_at, $this->expires_at);
        }

        return $this->access_token;
    }

    /**
     * Retrieve the access token expiration.
     * Check cache first, otherwise request a new access token from Walmart and save.
     *
     * @return Carbon
     */
    protected function getExpiresAt(): Carbon
    {
        if (!is_null($this->expires_at)) {
            return $this->expires_at;
        }

        $cachedExpiresAt = Cache::get($this->getExpiresAtCacheKey());

        if (is_null($cachedExpiresAt)) {
            $this->getAccessToken();
        } else {
            $this->expires_at = $cachedExpiresAt;
        }

        return $this->expires_at;
    }

    /**
     * Generate a cache key based on the record's id.
     *
     * @return string
     */
    public function getAccessTokenCacheKey(): string
    {
        return "walmart-api.access_token.{$this->id}";
    }

    /**
     * Generate a cache key based on the record's id.
     *
     * @return string
     */
    public function getExpiresAtCacheKey(): string
    {
        return "walmart-api.expires_at.{$this->id}";
    }
}
