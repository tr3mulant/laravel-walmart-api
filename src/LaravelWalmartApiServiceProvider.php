<?php

namespace TremulantTech\LaravelWalmartApi;

use Illuminate\Support\ServiceProvider;
use Walmart\AccessToken;
use Walmart\Enums\Country;

class LaravelWalmartApiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services. Bind each api class to the Service
     * Container with a dummy configuration.
     *
     * @return void
     */
    public function register()
    {
        foreach (LaravelWalmartApi::API_CLASSES as $cls) {
            $placeholderConfig = new Configuration(true, [
                'client_id' => 'DUMMY',
                'client_secret' => 'DUMMY',
                'consumer_id' => 'DUMMY',
                'private_key' => 'DUMMY',
                'channel_type' => 'DUMMY',
                'partner_id' => 'DUMMY',
                'access_token' => new AccessToken('DUMMY', \Carbon\Carbon::now()),
                'country' => $this->handleCountry($cls),
            ]);

            $this->app->bind($cls, fn () => new $cls($placeholderConfig));
        }
    }

    /**
     * Bootstrap any application services. Publishes the config
     * config/walmart-api.php. Publishes migrations for walmart_api_sellers and
     * walmart_api_credentials tables.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/walmart-api.php' => config_path('walmart-api.php')
        ], 'config');

        $time = time();

        $sellerTable = database_path('migrations/' . date('Y_m_d_his', $time) . '_create_walmart_api_sellers_table.php');

        $credentialsTable = database_path('migrations/' . date('Y_m_d_his', $time + 1) . '_create_walmart_api_credentials_table.php');

        $this->publishes([
            __DIR__.'/../database/migrations/create_walmart_api_sellers_table.php' => $sellerTable,
            __DIR__.'/../database/migrations/create_walmart_api_credentials_table.php' => $credentialsTable,
        ], 'migrations');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return LaravelWalmartApi::API_CLASSES;
    }

    /**
     * Try to convert a namespace to country for the dummy configuration.
     *
     * @param string $cls
     * @return string
     * @throws \InvalidArgumentException
     */
    private function handleCountry(string $cls): string
    {
        $country = str($cls)->beforeLast('\\')->afterLast('\\')->lower()->toString();

        if (in_array($country, Country::all())) {
            return $country;
        }

        throw new \InvalidArgumentException("Invalid Walmart API class: $cls");
    }
}
