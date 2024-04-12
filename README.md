# Walmart Api wrapper for Laravel projects

This package utilizes the library [highsidelabs/walmart-api-php](<[https://](https://github.com/highsidelabs/walmart-api-php)>) and was inspired by [highsidelabs/laravel-spapi](https://github.com/highsidelabs/laravel-spapi).

## Install

This package is not published to packagist so modify your `composer.json`'s repositories key and require key.

```json
{
  "name": "my project",
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/tr3mulant/laravel-walmart-api.git"
    }
  ],
  "require": {
    "tremulanttech/laravel-walmart-api": "dev-main"
  }
}
```

## Setup

Publish the config file

```bash
$ php artisan vendor:publish --provider="TremulantTech\LaravelWalmartApi\LaravelWalmartApiServiceProvider" --tag="config"
```

Publish the migrations

```bash
$ php artisan vendor:publish --provider="TremulantTech\LaravelWalmartApi\LaravelWalmartApiServiceProvider" --tag="migrations"
```

## Usage

This package ships with two Eloquent models that are needed for configuration of the Walmart Api library.

1. `Seller` model - an owner record for the Walmart Seller account.
2. `Credential` model - Seller owned set of credentials by an `Eloquent HasMany` relationship.

Create a Seller and a set of Credentials

```php
<?php

use TremulantTech\LaravelWalmartApi\Models\Seller;
use TremulantTech\LaravelWalmartApi\Models\Credentials;

$seller = Seller::firstOrCreate([
    'name' => config('walmart-api.seller.name', 'My Seller'),
]);

$credentials = $seller->credentials()->firstOrCreate([
    'client_id' => config('walmart-api.credentials.client_id'),
    'client_secret' => config('walmart-api.credentials.client_secret'),
    'consumer_id' => config('walmart-api.credentials.consumer_id'),
    'private_key' => config('walmart-api.credentials.private_key'),
    'channel_type' => config('walmart-api.credentials.channel_type'),
    'partner_id' => config('walmart-api.credentials.partner_id'),
    'grant_type' => config('walmart-api.credentials.grant_type'),
    'country' => config('walmart-api.credentials.country'),
]);

```

With a set of credentials created, leverage Laravel's `Service Container` to inject the dependency into a class.

```php
<?php

use TremulantTech\LaravelWalmartApi\Models\Credentials;
use Walmart\Apis\BaseApi;
use Walmart\Apis\MP\US\SettingsApi;
use Walmart\ApiException;

public class WalmartSettingsApiController extends Controller {

    private BaseApi $api;

    /**
    * This will inject a SettingsApi instance with a set of dummy credentials.
    * Apply the needed credentials to the api instance and we're set to consume endpoints
    * for the settings resource. You can also inject the Credentials model here if your
    * Service Container can resolve the correct record.
    */
    public function __construct(SettingsApi $api) {
        $credentials = Credentials::first();

        $this->api = $credentials->applyTo($api);
    }

    public function getAllShippingTemplates()
    {
        try {
            $response = $this->api->getAllShippingTemplates();

            return response()->json($response->getResponseBody())
        } catch (ApiException $e) {
            return response()->json($e->getResponseBody(), $e->getCode());
        }
    }
}

```

If you prefer to use static methods, make an api instance with the `LaravelWalmartApi::make(string $apiClass, Credentials $credentials)`.

```php
<?php

use TremulantTech\LaravelWalmartApi;
use TremulantTech\LaravelWalmartApi\Models\Credentials;
use Walmart\Apis\MP;
use Walmart\ApiException;

public class WalmartFeedService {
    public function __construct(Credentials $credentials) {
        $this->credentials = $credentials;
    }

    public function submitFeed()
    {
        try {
            $feedsApi = LaravelWalmartApi::make(
                MP\US\FeedsApi::class,
                $this->credentials
            );

            $response = $feedsApi->submit($feed);

            return json_decode($response);
        } catch (ApiException $e) {
            return json_decode($e->getResponseBody())
        }
    }
}

```

Another variant to static method use. This is similar to `highsidelabs/walmart-api` package's `Walmart::marketplace($config)->auth()` syntax.

```php
<?php

use TremulantTech\LaravelWalmartApi;
use TremulantTech\LaravelWalmartApi\Models\Credentials;

public class WalmartGetTokenDetailAction
{
    private ?Credentials $credentials = null;

    public function getTokenDetail()
    {
        return $this->getActionService()->getTokenDetail();
    }

    private function getActionService()
    {
        if (!$this->credentials) {
            $this->credentials = Credentials::first();
        }

        return LaravelWalmartApi::provider('marketplace', $this->credentials)->auth();
    }
}

```
