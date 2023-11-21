<?php

/**
 * Currency Management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Currencies extends AppModel
{
    /**
     * Initialize Currencies
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['currencies']);
    }

    /**
     * Fetches a list of all currencies for a given company
     *
     * @param int $company_id The ID of the company to fetch currencies for
     * @param int $page The page to return results for (optional, default 1)
     * @param string $order_by The sort and order conditions (e.g. array('sort_field'=>"ASC"), optional)
     * @return mixed An array of stdClass objects representing currencies, false if no currencies found
     */
    public function getList($company_id, $page = 1, $order_by = ['code' => 'ASC'])
    {
        $this->Record = $this->getCurrencies($company_id);

        // Return the results
        return $this->Record->order($order_by)->
            limit($this->getPerPage(), (max(1, $page) - 1) * $this->getPerPage())->fetchAll();
    }

    /**
     * Returns the total number of currencies returned from Currencies::getList(),
     * useful in constructing pagination for the getList() method.
     *
     * @param int $company_id The ID of the company to fetch currencies for
     * @return int The total number of currencies
     * @see Currencies::getList()
     */
    public function getListCount($company_id)
    {
        $this->Record = $this->getCurrencies($company_id);

        // Return the number of results
        return $this->Record->numResults();
    }

    /**
     * Fetches a full list of all currencies for a given company
     *
     * @param int $company_id The ID of the company to fetch currencies for
     * @return mixed An array of stdClass objects representing all currencies, or false if no currencies found
     */
    public function getAll($company_id)
    {
        $this->Record = $this->getCurrencies($company_id);

        return $this->Record->fetchAll();
    }

    /**
     * Partially constructs the query required by Currencies::getList(),
     * Currencies::getListCount(), and Currencies::getAll()
     *
     * @param int $company_id The company ID to fetch currencies for
     * @return Record The partially constructed query Record object
     */
    private function getCurrencies($company_id)
    {
        $this->Record->select()->from('currencies')->
            where('company_id', '=', $company_id);

        return $this->Record;
    }

    /**
     * Fetches a currency using the given currency code
     *
     * @param string $currency_code The ISO 4217 currency code to fetch on
     * @param int $company_id The ID of the company whose currency to fetch
     * @return mixed stdClass object representing the currency if it exists, false otherwise
     */
    public function get($currency_code, $company_id)
    {
        // Select from currencies
        return $this->Record->select()->from('currencies')->where('company_id', '=', $company_id)->
            where('code', '=', $currency_code)->fetch();
    }

    /**
     * Returns an array containing every supported currency format with an example in key/value pairs
     *
     * @return array An array of currency formats in key/value pairs where the
     *  key is the format and the value is an example
     */
    public function getFormats()
    {
        $formats = [];
        $temp_formats = ['#,###.##', '#.###,##', '# ###.##', '# ###,##',
            '#,##,###.##', '# ###', '#.###', '#,###', '####.##', '####,##'];
        foreach ($temp_formats as $format) {
            $example = $format;
            $max_size = strlen($format);
            $i = 0;
            while ($i < $max_size && ($p = strpos($example, '#')) !== false) {
                $example[$p] = (++$i) % 10;
            }
            $formats[$format] = $example;
        }
        return $formats;
    }

    /**
     * Adds a new currency
     *
     * @param array $vars An array of currency info including:
     *
     *  - code The ISO 4217 currency code
     *  - company_id The Company ID
     *  - format The format code to use for this currency
     *  - precision The decimal precision to use for this currency
     *  - prefix The prefix to use for this currency
     *  - suffix The suffix to use for this currency
     *  - exchange_rate The exchange rate in relation to the primary currency
     *  - exchange_updated The datetime the exchange_rate was last updated
     */
    public function add(array $vars)
    {
        // Set the time the exchange rate was added
        if (empty($vars['exchange_updated'])) {
            $vars['exchange_updated'] = date('c');
        }

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            // Add a currency
            $fields = ['code', 'company_id', 'format', 'precision',
                'prefix', 'suffix', 'exchange_rate', 'exchange_updated'
            ];
            $this->Record->insert('currencies', $vars, $fields);
        }
    }

    /**
     * Edits an existing currency
     *
     * @param string $currency_code The ISO 4217 currency code we are updating
     * @param int $company_id The Company ID for this currency code
     * @param array $vars An array of currency info including:
     *
     *  - format The format code to use for this currency
     *  - precision The decimal precision to use for this currency
     *  - prefix The prefix to use for this currency
     *  - suffix The suffix to use for this currency
     *  - exchange_rate The exchange rate in relation to the primary currency
     *  - exchange_updated The datetime the exchange_rate was last updated
     */
    public function edit($currency_code, $company_id, array $vars)
    {
        // Set the time the exchange rate was updated
        if (empty($vars['exchange_updated'])) {
            $vars['exchange_updated'] = date('c');
        }

        // Validate code and company ID correspond to a currency
        $vars['code'] = $currency_code;
        $vars['company_id'] = $company_id;

        $rules = $this->getRules($vars);

        // Remove constraint to negate the existing currency, we need to ensure
        // that this currency does exist
        unset($rules['code']['exists']['negate']);

        $this->Input->setRules($rules);

        if ($this->Input->validates($vars)) {
            // Update a currency
            $fields = ['format', 'precision', 'prefix', 'suffix', 'exchange_rate', 'exchange_updated'];
            $this->Record->where('company_id', '=', $company_id)->
                where('code', '=', $currency_code)->update('currencies', $vars, $fields);
        }
    }

    /**
     * Deletes an existing currency
     *
     * @param string $currency_code The ISO 4217 currency code we are deleting
     * @param int $company_id The Company ID for this currency code
     */
    public function delete($currency_code, $company_id)
    {
        $rules = [
            'currency_code' => [
                'in_use' => [
                    'rule' => [[$this, 'validateCurrencyInUse'], $company_id],
                    'negate' => true,
                    'message' => $this->_('Currencies.!error.currency_code.in_use', $currency_code)
                ],
                'default' => [
                    'rule' => [[$this, 'validateCurrencyIsDefault'], $company_id],
                    'negate' => true,
                    'message' => $this->_('Currencies.!error.currency_code.is_default', $currency_code)
                ]
            ]
        ];

        $this->Input->setRules($rules);

        $vars = ['currency_code' => $currency_code];

        if ($this->Input->validates($vars)) {
            $this->Record->from('currencies')->where('code', '=', $currency_code)->
                where('company_id', '=', $company_id)->delete();
        }
    }

    /**
     * Converts an amount from one currency to another using the exchange rate
     *
     * @param float $amount The amount to convert
     * @param string $from_currency The ISO 4217 currency code format representing $amount
     * @param string $to_currency The ISO 4217 currency code to format to
     * @param int $company_id The ID of the company
     * @return float The $amount converted between currencies
     */
    public function convert($amount, $from_currency, $to_currency, $company_id)
    {
        // Convert the amount
        if (($from = $this->get($from_currency, $company_id)) && (($to = $this->get($to_currency, $company_id)))) {
            // Convert the currency
            $from_amount = $this->toDecimal($amount, $from->code, $company_id, 4);
            $to_amount = ($to->exchange_rate == 0) ? 0 : ($from_amount * ($to->exchange_rate / $from->exchange_rate));

            return $this->toDecimal($to_amount, $to->code, $company_id, 4);
        }

        return $amount;
    }

    /**
     * Converts a currency value into a decimal representation based on ISO 31-0
     *
     * @param string $value A currency value
     * @param string $currency The ISO 4217 currency code format representing $value
     * @param int $company_id The company ID to fetch the currency for
     * @param int $decimals The number of decimal places (precision) (optional, default null to use the currency's
     *  defined precision)
     * @return string The value in decimal format based on ISO 31-0
     * @see Currencies::toCurrency()
     */
    public function toDecimal($value, $currency, $company_id = null, $decimals = null)
    {
        // Currency should be a 3-character string
        if (!is_string($currency) || strlen($currency) != 3) {
            return $value;
        }

        // Cache all currency formats for future use
        static $formats = [];

        // Use the current company if not specified
        if ($company_id === null) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $value = trim($value ?? '');

        // If no value set, assume 0
        if ($value == '') {
            $value = 0;
        }

        // Cache the currency format so we don't have to look it up again
        if (!isset($formats[$currency][$company_id])) {
            $formats[$currency][$company_id] = $this->get($currency, $company_id);
        }

        // Set the decimal precision to the currency's precision
        if ($decimals === null || !is_numeric($decimals)) {
            $decimals = ($formats[$currency][$company_id] ? $formats[$currency][$company_id]->precision : 2);
        }

        // If value is already in decimal format, return it
        if (is_float($value) || (is_numeric($value) && str_contains($value, '.'))) {
            return number_format($this->truncateDecimal($value, $decimals, '.'), $decimals, '.', '');
        }

        // Perform decimal conversion
        if ($formats[$currency][$company_id]) {
            // Remove any prefix and/or suffix that may exist
            $value = ltrim($value, $formats[$currency][$company_id]->prefix ?? '');
            $value = rtrim($value, $formats[$currency][$company_id]->suffix ?? '');

            $decimal = substr($formats[$currency][$company_id]->format, -3, 1);

            // Period based decimal place
            if ($decimal == '.') {
                $value = str_replace([',', ' '], '', $value);
            } elseif ($decimal == ',') {
                // Comma based decimal place
                $value = str_replace(['.', ' ', ','], ['', '', '.'], $value);
            } elseif ($decimal == '#') {
                // No decimal place
                // Handle cases where decimal places may appear
                switch (substr($formats[$currency][$company_id]->format, 1, 1)) {
                    // decimal must be ','
                    case '.':
                        $value = str_replace(['.', ' ', ','], ['', '', '.'], $value);
                        break;
                    // decimal must be '.'
                    case ' ':
                        $value = str_replace([',', ' '], '', $value);
                        break;
                    // decimal must be '.'
                    case ',':
                        $value = str_replace([',', ' '], '', $value);
                        break;
                    default:
                        $value = str_replace([',', ' ', '.'], '', $value);
                        break;
                }
            } elseif ($decimal == ' ') {
                // Space based decimal place
                $value = str_replace(['.', ',', ' '], ['', '', '.'], $value);
            }
        }
        // Format value as decimal based on ISO 31-0
        return $this->truncateDecimal(number_format((float) $value, $decimals, '.', ''), $decimals, '.');
    }

    /**
     * Converts the given decimal value into a currency value.
     *
     * @param float $value The decimal value to convert
     * @param string $currency The ISO 4217 currency code format to apply to $value
     * @param int $company_id The company ID to fetch the currency for
     * @param bool $prefix True to include the prefix symbol for this currency, false otherwise
     * @param bool $suffix True to include the suffix symbol for this currency, false otherwise
     * @param bool $code True to include the currency code for this currency, false otherwise
     * @param bool $with_separator True to include the separator for this currency, false otherwise
     * @param int $decimals The number of decimal places (precision) (optional, default null to use the currency's
     *  defined precision)
     * @return string The value formatted for the specified currency
     * @see Currencies::toDecimal()
     * @see Currencies::toCurrencyValue()
     */
    public function toCurrency(
        $value,
        $currency,
        $company_id = null,
        $prefix = true,
        $suffix = true,
        $code = false,
        $with_separator = true,
        $decimals = null
    ) {
        if ($company_id === null) {
            $company_id = Configure::get('Blesta.company_id');
        }

        static $formats = [];

        // If the currency is not cached, look it up
        if (!isset($formats[$currency][$company_id])) {
            $formats[$currency][$company_id] = $this->get($currency, $company_id);
        }

        // If the currency wasn't found, return the value as-is
        if (!isset($formats[$currency][$company_id]->format)) {
            return $value;
        }

        // If no value set, assume 0
        if ($value == '' || !is_numeric($value)) {
            $value = $this->toDecimal($value, $currency);

            if ($value == '' || !is_numeric($value)) {
                $value = 0;
            }
        }

        // Set the decimal precision to the currency's precision
        if ($decimals === null || !is_numeric($decimals)) {
            $decimals = (isset($formats[$currency][$company_id]) ? $formats[$currency][$company_id]->precision : 2);
        }

        // Format the currency
        switch ($formats[$currency][$company_id]->format) {
            // Values with decimal separator
            case '#,###.##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, '.', $with_separator ? ',' : ''),
                    $decimals,
                    '.'
                );
                break;
            case '#.###,##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, ',', $with_separator ? '.' : ''),
                    $decimals,
                    ','
                );
                break;
            case '# ###.##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, '.', $with_separator ? ' ' : ''),
                    $decimals,
                    '.'
                );
                break;
            case '# ###,##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, ',', $with_separator ? ' ' : ''),
                    $decimals,
                    ','
                );
                break;
            case '#,##,###.##':
                $value = number_format($value, $decimals, '.', '');
                // If value over 1000 need to format specially
                if ($with_separator && $value >= 1000) {
                    $thousandths_length = strlen($value) - ($decimals + 2);
                    $temp_val = $value;
                    $value = substr($temp_val, -($decimals + 1)); // hold decimals
                    // format value
                    for ($j = 0, $i = $thousandths_length; $i >= 0; $i--, $j++) {
                        // Thousandths place
                        if ($j < 3) {
                            $value = $temp_val[$i] . $value;
                        } else {
                            // All others
                            $value = $temp_val[$i] . (($j + 3) % 2 == 0 ? ',' : '') . $value;
                        }
                    }
                }
                $value = $this->truncateDecimal($value, $decimals, '.');
                break;
            // Values with no decimal separator (unless explicitly given)
            case '# ###':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, '.', $with_separator ? ' ' : ''),
                    $decimals,
                    '.'
                );
                break;
            case '#.###':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, ',', $with_separator ? '.' : ''),
                    $decimals,
                    ','
                );
                break;
            case '#,###':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, '.', $with_separator ? ',' : ''),
                    $decimals,
                    '.'
                );
                break;
            case '####.##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, '.', ''),
                    $decimals,
                    '.'
                );
                break;
            case '####,##':
                $value = $this->truncateDecimal(
                    number_format($value, $decimals, ',', ''),
                    $decimals,
                    ','
                );
                break;
            default:
                return $value; // format was unrecognized, return the value as-is
        }

        return ($prefix ? $formats[$currency][$company_id]->prefix : '') . $value .
            ($suffix ? $formats[$currency][$company_id]->suffix : '') .
            ($code ? ' ' . $currency : '');
    }

    /**
     * Converts the given decimal value into a currency value excluding the prefix, suffix, and currency code symbols
     *
     * @param float $value The decimal value to convert
     * @param string $currency The ISO 4217 currency code format to apply to $value
     * @param bool $with_separator True to include the separator for this currency, false otherwise
     * @param int $company_id The company ID to fetch the currency for
     * @param int $decimals The number of decimal places (precision) (optional, default null to use the currency's
     *  defined precision)
     * @return string The value formatted for the specified currency
     * @see Currencies::toCurrency()
     */
    public function toCurrencyValue(
        $value,
        $currency,
        $with_separator = true,
        $company_id = null,
        $decimals = null
    ) {
        return $this->toCurrency($value, $currency, $company_id, false, false, false, $with_separator, $decimals);
    }

    /**
     * Returns the rule set for adding/editing currencies
     *
     * @param array $vars The input vars
     * @return array Currency rules
     */
    private function getRules($vars)
    {
        $rules = [
            'code' => [
                'length' => [
                    'rule' => ['matches', '/^[A-Z]{3}$/'],
                    'message' => $this->_('Currencies.!error.code.length')
                ],
                'exists' => [
                    'rule' => [[$this, 'validateCurrencyExists'], (isset($vars['company_id']) ? $vars['company_id'] : null)],
                    'negate' => true,
                    'message' => $this->_(
                        'Currencies.!error.code.exists',
                        (isset($vars['code']) ? $vars['code'] : null),
                        (isset($vars['company_id']) ? $vars['company_id'] : null)
                    )
                ]
            ],
            'company_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'companies'],
                    'message' => $this->_('Currencies.!error.company_id.exists')
                ]
            ],
            'format' => [
                'format' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateCurrencyFormat']],
                    'message' => $this->_('Currencies.!error.format.format')
                ]
            ],
            'precision' => [
                'format' => [
                    'if_set' => true,
                    'rule' => ['in_array', ['0', '1', '2', '3', '4']],
                    'message' => $this->_('Currencies.!error.precision.format')
                ]
            ],
            'prefix' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 10],
                    'message' => $this->_('Currencies.!error.prefix.length')
                ]
            ],
            'suffix' => [
                'length' => [
                    'if_set' => true,
                    'rule' => ['maxLength', 10],
                    'message' => $this->_('Currencies.!error.suffix.length')
                ]
            ],
            'exchange_rate' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'is_numeric',
                    'message' => $this->_('Currencies.!error.exchange_rate.format')
                ]
            ],
            'exchange_updated' => [
                'format' => [
                    'if_set' => true,
                    'rule' => 'isDate',
                    'post_format' => [[$this, 'dateToUtc']],
                    'message' => $this->_('Currencies.!error.exchange_updated.format')
                ]
            ]
        ];
        return $rules;
    }

    /**
     * Checks if a currency code is currently in use
     *
     * @param string $currency_code The ISO 4217 currency code to check
     * @param int $company_id The company ID whose currency to check
     * @return bool True if the currency code is in use, false otherwise
     */
    public function validateCurrencyInUse($currency_code, $company_id)
    {
        // A currency in use in Invoices, Package pricing, or Coupon amounts
        // cannot be deleted
        // Get number of invoices in the given currency
        $count = $this->Record->select('invoices.id')->from('invoices')->
            innerJoin('clients', 'clients.id', '=', 'invoices.client_id', false)->
            innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)->
            where('invoices.currency', '=', $currency_code)->
            where('client_groups.company_id', '=', $company_id)->numResults();

        if ($count > 0) {
            return true;
        }

        // Get number of package pricing amounts in the given currency
        $count += $this->Record->select('package_pricing.id')->from('package_pricing')->
            innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)->
            innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)->
            where('pricings.currency', '=', $currency_code)->
            where('packages.company_id', '=', $company_id)->numResults();

        if ($count > 0) {
            return true;
        }

        // Get number of coupon amounts in the given currency
        $count += $this->Record->select('coupon_amounts.*')->from('coupon_amounts')->
            innerJoin('coupons', 'coupons.id', '=', 'coupon_amounts.coupon_id', false)->
            where('coupons.company_id', '=', $company_id)->
            where('coupon_amounts.currency', '=', $currency_code)->
            numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Checks if the given currency code is set as the default currency
     *
     * @param string $currency_code The ISO 4217 currency code to check
     * @param int $company_id The company ID whose default currency to check
     * @return bool True if the currency is the default currency for this company, false otherwise
     */
    public function validateCurrencyIsDefault($currency_code, $company_id)
    {
        Loader::loadComponents($this, ['SettingsCollection']);
        $default_currency = $this->SettingsCollection->fetchSetting(null, $company_id, 'default_currency');

        return (
            isset($default_currency['value']) && preg_match('/' . $default_currency['value'] . '/i', $currency_code)
        );
    }

    /**
     * Validates a currency's 'format' field
     *
     * @param string $format The format to check, (e.g. "#.###")
     * @return bool True if validated, false otherwise
     */
    public function validateCurrencyFormat($format)
    {
        switch ($format) {
            case '#,###.##':
            // no break
            case '#.###,##':
            // no break
            case '# ###.##':
            // no break
            case '# ###,##':
            // no break
            case '#,##,###.##':
            // no break
            case '# ###':
            // no break
            case '#.###':
            // no break
            case '#,###':
            // no break
            case '####.##':
            // no break
            case '####,##':
                return true;
        }
        return false;
    }

    /**
     * Validates a currency exists
     *
     * @param string $code The currency code
     * @param int $company_id The company ID
     * @return bool True if the currency exists, false otherwise
     */
    public function validateCurrencyExists($code, $company_id)
    {
        $count = $this->Record->select('code')->from('currencies')->
            where('code', '=', $code)->where('company_id', '=', $company_id)->numResults();

        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * Retrieves a list of exchange rate processors
     *
     * @return array A list of exchange rate processors
     */
    public function getExchangeRateProcessors()
    {
        $processors = [];

        // Look for the exchange rates component and all processors
        $dir = opendir(COMPONENTDIR . 'exchange_rates');

        while (false !== ($file = readdir($dir))) {
            // If the file is not a hidden file, and is a directory, it is a processor
            if (substr($file, 0, 1) != '.' && is_dir(COMPONENTDIR . 'exchange_rates' . DS . $file)) {
                $processors[$file] = ucwords(str_replace('_', ' ', $file));
            }
        }

        return $processors;
    }

    /**
     * Retrieves an instance of the exchange rate processor.
     * Sets Input errors if the processor cannot not be found.
     *
     * @param string $processor The name of the exchange rate processor (e.g. google_finance)
     * @return bool|ExchangeRate An instance of the ExchangeRate processor if found, otherwise boolean false
     */
    public function getExchangeRateProcessor($processor)
    {
        if (!isset($this->ExchangeRates)) {
            Loader::loadComponents($this, ['ExchangeRates']);
        }
        if (!isset($this->Net)) {
            Loader::loadComponents($this, ['Net']);
        }

        $rate_processor = false;

        if (!empty($processor)) {
            // Attempt to create the processor
            try {
                $rate_processor = $this->ExchangeRates->create($processor, [$this->Net->create('Http')]);
            } catch (Exception $e) {
                // Error, invalid processor
                $this->Input->setErrors([
                    'processor' => [
                        'invalid' => $this->_('Currencies.!error.processor.invalid', true)
                    ]
                ]);
            }
        } else {
            // Error, no processor is set
            $this->Input->setErrors(['processor' => ['empty' => $this->_('Currencies.!error.processor.empty', true)]]);
        }

        return $rate_processor;
    }

    /**
     * Updates all currency exchange rates for the current company using the
     * configured processor.
     */
    public function updateRates()
    {
        $company_id = Configure::get('Blesta.company_id');

        // Get the company settings
        Loader::loadComponents($this, ['SettingsCollection']);
        $company_settings = $this->SettingsCollection->fetchSettings(null, $company_id);

        // Get the exchange rate processor
        $rate_processor = $this->getExchangeRateProcessor((isset($company_settings['exchange_rates_processor']) ? $company_settings['exchange_rates_processor'] : null));

        // Update currencies rates
        if ($rate_processor !== false) {
            // Set default currency
            $default_currency = (isset($company_settings['default_currency']) ? $company_settings['default_currency'] : null);

            // Set API Key
            if ($rate_processor->requiresKey()) {
                $rate_processor->setKey((isset($company_settings['exchange_rates_processor_key']) ? $company_settings['exchange_rates_processor_key'] : null));
            }

            // Get the exchange rate padding value
            $pad_value = (isset($company_settings['exchange_rates_padding']) ? $company_settings['exchange_rates_padding'] : 0);

            $currencies = $this->getAll($company_id);
            foreach ($currencies as $currency) {
                // Default currency rate must be 1
                if ($currency->code == $default_currency) {
                    $this->edit($currency->code, $company_id, ['exchange_rate' => 1, 'exchange_updated' => date('c')]);
                    continue;
                }

                $rate = $rate_processor->getRate($default_currency, $currency->code);

                if ($rate && $rate['rate'] > 0) {
                    // Pad the exchange rate
                    if (is_numeric($pad_value)) {
                        $rate['rate'] += ($rate['rate'] * $pad_value / 100);
                    }

                    // Set exchange_rate_updated date with time including offset so
                    // Currencies::edit() will not convert from Blesta timezone setting
                    $this->edit(
                        $currency->code,
                        $company_id,
                        ['exchange_rate' => $rate['rate'], 'exchange_updated' => date('c')]
                    );
                }
            }
        }
    }
}
