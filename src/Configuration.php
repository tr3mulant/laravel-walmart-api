<?php

namespace TremulantTech\LaravelWalmartApi;

use Walmart\AccessToken;
use Walmart\Configuration as WalmartConfiguration;

class Configuration extends WalmartConfiguration
{
    /**
     * Flag to use a dummy config.
     *
     * @var bool
     */
    private bool $dummy;

    /**
     * Build a Configuration object.
     *
     * @param bool $dummy
     * @param array $options
     */
    public function __construct(bool $dummy, array $options)
    {
        parent::__construct($this->prepareOptions($options));

        $this->dummy = $dummy;
    }

    /**
     * Sign the request with the user's private key.
     *
     * @param string $path The path to the endpoint being called
     * @param string $method The HTTP method being used to call the endpoint
     * @param int $timestamp The timestamp of the request, to millisecond precision
     * @param string $query The querystring of the request, if any
     * @throws RuntimeException
     * @return string The request signature
     */
    protected function sign(string $path, string $method, int $timestamp, string $query = ''): string
    {
        if ($this->dummy) {
            throw new \Exception('Cannot sign a request with invalid credentials');
        }

        return parent::sign($path, $method, $timestamp, $query);
    }

    /**
     * Maps Credential model attributes to WalmartConfiguration properties.
     * Creates an AccessToken if given.
     *
     * @param array $options
     * @return array
     */
    private function prepareOptions(array $options): array
    {
        foreach ($options as $k => $v) {
            $parts = explode('_', $k);

            foreach ($parts as $n => $part) {
                if ($n) {
                    $parts[$n] = ucfirst($part);
                }
            }

            unset($options[$k]);

            if (!is_null($v)) {
                $options[implode('', $parts)] = $v;
            }
        }

        if (is_string($options['accessToken'] ?? null)) {
            $options['accessToken'] = new AccessToken($options['accessToken'], $options['expiresAt']);
        }

        unset($options['expiresAt']);

        return $options;
    }
}
