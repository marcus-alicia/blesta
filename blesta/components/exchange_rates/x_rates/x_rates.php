<?php
/**
 * X-Rates Currency Exchange Rate Processor
 *
 * @package blesta
 * @subpackage blesta.components.exchange_rates.x_rates
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class XRates extends ExchangeRate
{
    /**
     * @var string The URL to the currency exchange rate resource
     */
    private static $url = 'https://www.x-rates.com/calculator/';
    /**
     * @var int The maximum number of seconds to wait for a response
     */
    private static $timeout = 30;

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
        $params = [
            'amount' => $amount,
            'from' => $currency_from,
            'to' => $currency_to
        ];

        $this->Http->open();
        $this->Http->setTimeout(self::$timeout);
        $response = $this->Http->get(self::$url . '?' . http_build_query($params));
        $this->Http->close();

        if ($response && ($rate = $this->parseRate($response))) {
            return [
                'rate' => $rate,
                'updated' => $this->Date->format('Y-m-d H:i:s', date('c'))
            ];
        }

        return false;
    }

    /**
     * Parses HTML to determine the conversion rate
     *
     * @param string $html The HTML page containing the rate
     * @return mixed The conversion rate, if available, otherwise boolean false
     */
    private function parseRate($html)
    {
        $rate = false;

        if (!empty($html)) {
            $regex = <<< REGEX
/class=["\']{0,1}ccOutputRslt["\']{0,1}>([0-9]*\.[0-9]*)<span class=["\']{0,1}ccOutputTrail["\']{0,1}>([0-9]*)</i
REGEX;
            $matches = [];
            preg_match($regex, $html, $matches);

            // Parse the first part of the number containing 3 significant digits (e.g. 1.123)
            if (isset($matches[1]) && is_numeric(trim($matches[1]))) {
                $rate = trim($matches[1]);

                // The remaining 3 significant digits are in another span tag, which should be appended (e.g. 456)
                if (isset($matches[2]) && is_numeric(trim($matches[2]))) {
                    // Result: 1.123456
                    $rate .= trim($matches[2]);
                }
            }
        }

        return $rate;
    }
}
