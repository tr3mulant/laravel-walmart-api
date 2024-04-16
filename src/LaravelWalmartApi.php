<?php

namespace TremulantTech\LaravelWalmartApi;

use TremulantTech\LaravelWalmartApi\Models\Credentials;
use Walmart\Apis\BaseApi;
use Walmart\Apis\CP;
use Walmart\Apis\MP;
use Walmart\Apis\Supplier;
use Walmart\Walmart;

final class LaravelWalmartApi extends Walmart
{
    /**
     * All of the API Classes supported by the highsidelabs/walmart-api
     * package.
     * @var array
     */
    public const API_CLASSES = [
        MP\US\AssortmentRecommendationsApi::class,
        MP\MX\AuthenticationApi::class,
        MP\US\AuthenticationApi::class,
        MP\CA\EventsApi::class,
        MP\CA\FeedsApi::class,
        MP\MX\FeedsApi::class,
        MP\US\FeedsApi::class,
        MP\US\FulfillmentApi::class,
        MP\US\InsightsApi::class,
        MP\CA\InventoryApi::class,
        MP\MX\InventoryApi::class,
        MP\US\InventoryApi::class,
        MP\CA\ItemsApi::class,
        MP\MX\ItemsApi::class,
        MP\US\ItemsApi::class,
        MP\US\LagTimeApi::class,
        MP\US\NotificationsApi::class,
        MP\US\OnRequestReportsApi::class,
        MP\CA\OrdersApi::class,
        MP\MX\OrdersApi::class,
        MP\US\OrdersApi::class,
        MP\CA\PricesApi::class,
        MP\MX\PricesApi::class,
        MP\US\PricesApi::class,
        MP\CA\PromotionsApi::class,
        MP\US\PromotionsApi::class,
        MP\CA\ReportsApi::class,
        MP\MX\ReportsApi::class,
        MP\US\ReportsApi::class,
        MP\MX\ReturnsApi::class,
        MP\US\ReturnsApi::class,
        MP\US\ReviewsApi::class,
        MP\CA\InternationalShippingApi::class,
        MP\MX\InternationalShippingApi::class,
        MP\US\RulesApi::class,
        MP\US\SettingsApi::class,
        MP\US\UtilitiesApi::class,
        CP\US\FeedsApi::class,
        Supplier\US\DSVCostApi::class,
        Supplier\US\DSVInventoryApi::class,
        Supplier\US\DSVLagTimeApi::class,
        Supplier\US\DSVOrdersApi::class,
        Supplier\US\FeedsApi::class,
        Supplier\US\ItemsApi::class,
        Supplier\US\ReportsApi::class,
    ];

    /**
     * All of the API provider classes supported by the
     * highsidelabs/walmart-api package.
     * @var array
     */
    public const API_PROVIDERS = [
        'marketplace' => MP\MarketplaceApi::class,
        'contentProvider' => CP\ContentProviderApi::class,
        'supplier' => Supplier\SupplierApi::class,
    ];

    /**
     * Creates an API instance with a set of Credentials.
     *
     * @param string $apiCls The SP API class to instantiate.
     * @param Credentials|int|string $credentials The Credentials or id of the
     * credentials to use for an SP API class.
     * @throws \InvalidArgumentException When attempting to make an unsupported
     * api class
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException When
     * missing credentials record.
     * @return BaseApi
     */
    public static function make(string $apiCls, Credentials|int|string $credentials): BaseApi
    {
        if (!in_array($apiCls, static::API_CLASSES, true)) {
            throw new \InvalidArgumentException("Invalid Walmart API class: $apiCls");
        }

        if (is_int($credentials) || is_string($credentials)) {
            $credentials = Credentials::findOrFail($credentials);
        }

        return new $apiCls($credentials->toApiConfiguration());
    }

    /**
     * Return a provider instance.
     *
     * @param string $provider
     * @param Credentials $credentials
     * @param bool $clone
     * @throws \BadMethodCallException When provider does not exist
     * @return Walmart
     */
    public static function provider(string $provider, Credentials $credentials, bool $clone = true): Walmart
    {
        if (!isset(self::API_PROVIDERS[$provider])) {
            throw new \BadMethodCallException("Method $provider does not exist");
        }

        return self::$provider($credentials->toApiConfiguration(), $clone);
    }
}
