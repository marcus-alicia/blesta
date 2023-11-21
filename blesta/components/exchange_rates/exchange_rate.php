<?php
/**
 * Abstract class that all Currency Exchange Rate Processors must extend
 *
 * @package blesta
 * @subpackage blesta.components.exchange_rates
 * @copyright Copyright (c) 2010-2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class ExchangeRate
{
    /**
     * @var string The API key
     */
    protected $key;

    /**
     * Initializes the exchange rate processor
     *
     * @param Http $Http The Http component to more easily facilitate HTTP requests to fetch data
     */
    abstract public function __construct(Http $Http);

    /**
     * Fetches the exchange rate from currency A to currency B using the given amount
     *
     * @param string $currency_from The ISO 4217 currency code to convert from
     * @param string $currency_to The ISO 4217 currency code to convert to
     * @param float $amount The amount to convert
     * @return mixed (bool) false on error or an array containing the exchange rate information including:
     *
     *  - rate
     *  - updated The date/time of the last update in YYYY-MM-DD HH:MM:SS format in UTC time
     */
    abstract public function getRate($currency_from, $currency_to, $amount = 1.0);

    /**
     * Determines whether an API key is necessary to retrieve exchange rates via the processor
     *
     * @return bool True if an API key is required, or false otherwise
     */
    public function requiresKey()
    {
        return false;
    }

    /**
     * Sets the API key necessary to retrieve exchange rates
     *
     * @param string $key The key for the exchange rate processor API
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Updates all currency rates given to convert the rates relative to the given currency
     *
     * @param string $to_currency The ISO 4217 currency code to convert relative to
     * @param array $rates A key/value list of all currencies and their rates. The $to_currency must be in the list
     * @return array A key/value list of all currency exchange rates
     */
    protected function convertRates($to_currency, array $rates)
    {
        // Determine the exchange rate of the currency we're converting to
        $base_rate = 0;
        if (isset($rates[$to_currency]) && $rates[$to_currency] != 0) {
            $base_rate = (1 / $rates[$to_currency]);
        }

        if ($base_rate == 0) {
            return [];
        }

        // Convert the rates
        foreach ($rates as $currency => &$rate) {
            // The currency rate we're converting to should itself be 1
            if ($currency == $to_currency) {
                $rate = 1;
                continue;
            }

            // The currency exchange rate is the rate of the old currency ($rate) times the relative
            // base rate of the to-currency
            $rate *= $base_rate;
        }

        return $rates;
    }
}
