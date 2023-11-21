<?php
/**
 * Fixer.io Exchange Rate Processor
 *
 * @package blesta
 * @subpackage blesta.components.exchange_rates.fixer
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Fixer extends ExchangeRate
{
    /**
     * @var string The URL protocol to use (paid plans support https, free plans support http)
     */
    private $protocol = 'https://';
    /**
     * @var string The URL to the currency exchange rate resource
     */
    private static $url = 'data.fixer.io/api/latest';
    /**
     * @var int The maximum number of seconds to wait for a response
     */
    private static $timeout = 30;
    /**
     * @var array The currency exchange rates keyed by the base currency
     */
    private $rates = [];

    /**
     * Initializes the exchange rate processor
     *
     * @param Http $Http The Http component to more easily facilitate HTTP requests to fetch data
     */
    public function __construct(Http $Http)
    {
        $this->Http = $Http;

        // Load Date helper if not set. We need the date in UTC
        if (!isset($this->Date)) {
            Loader::loadHelpers($this, ['Date' => [null, null, 'UTC']]);
        }
    }

    /**
     * Determines whether an API key is necessary to retrieve exchange rates via the processor
     *
     * @return bool True if an API key is required, or false otherwise
     */
    public function requiresKey()
    {
        return true;
    }

    /**
     * Fetches the exchange rate from currency A to currency B using the given amount
     *
     * @param string $currency_from The ISO 4217 currency code to convert from
     * @param string $currency_to The ISO 4217 currency code to convert to
     * @param float $amount The amount to convert
     * @return mixed (boolean) false on error or an array containing the exchange rate information including:
     *
     *  - rate The exchange rate for the supplied amount
     *  - updated The date/time of the last update in YYYY-MM-DD HH:MM:SS format in UTC time
     */
    public function getRate($currency_from, $currency_to, $amount = 1.0)
    {
        $rates = $this->getExchangeRates(strtoupper($currency_from));

        // Could not find any exchange rates
        if (empty($rates)) {
            return false;
        }

        // Since the rates are relative to the base rate ($currency_from) we must manually apply the amount multiplier
        $rate = null;
        $currency_to = strtoupper($currency_to);
        if (array_key_exists($currency_to, (array)$rates)) {
            $rate = $rates[$currency_to] * $amount;
        }

        if ($rate !== null && is_numeric($rate)) {
            return [
                'rate' => $rate,
                'updated' => $this->Date->format('Y-m-d H:i:s', date('c'))
            ];
        }

        return false;
    }

    /**
     * Retrieves a list of exchange rates relative to the given $currency_from
     *
     * @param string $currency_from The ISO 4217 currency code to convert from
     * @return array An array of currency exchange rates relative to the given currency
     */
    private function getExchangeRates($currency_from)
    {
        // Return the cached results if available
        if (!empty($this->rates[$currency_from])) {
            return $this->rates[$currency_from];
        }

        $params = [
            'access_key' => $this->key,
            'base' => $currency_from
        ];

        $this->Http->open();
        $this->Http->setTimeout(self::$timeout);
        $response = $this->Http->get($this->protocol . self::$url . '?' . http_build_query($params));
        $this->Http->close();

        // Cache and return the currencies
        if (!empty($response) && ($rates = json_decode($response))) {
            // Fetch the rates
            $formatted_rates = [];

            if (property_exists($rates, 'success') && $rates->success) {
                if (!empty($rates->rates)) {
                    $formatted_rates = (array)$rates->rates;
                }
            } elseif (property_exists($rates, 'error')
                && property_exists($rates->error, 'type')
                && $rates->error->type == 'https_access_restricted'
            ) {
                // Free plans can only connect to the endpoint via HTTP, but paid plans can connect via HTTPS
                // so fallback to HTTP if the API request fails and try again
                $this->protocol = 'http://';
                $formatted_rates = $this->getExchangeRates($currency_from);
            } elseif ($currency_from != 'EUR'
                && property_exists($rates, 'error')
                && !empty($rates->error->code)
                && $rates->error->code == 105
            ) {
                // If the reason is that we don't have access to source currency switching (free plans don't)
                // then fetch and convert the EUR values relative to the from currency ourselves
                $formatted_rates = $this->convertRates($currency_from, $this->getExchangeRates('EUR'));
            }

            // We have rate quotes to use
            if (!empty($formatted_rates)) {
                // Cache the rates relative to the currency so we don't need to fetch them again
                // if there are any other conversions done on them
                $this->rates[$currency_from] = $formatted_rates;

                return $this->rates[$currency_from];
            }
        }

        return [];
    }
}
