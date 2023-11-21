<?php
/**
 * Currency Format Helper
 *
 * Provides conversion routines to and from currency strings and float values.
 * Uses Blesta's Currencies model which provides automatic caching for increased
 * performance.
 *
 * @package blesta
 * @subpackage blesta.helpers.currency_format
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class CurrencyFormat
{
    /**
     * @var int The currently set company ID. Each company has their own conversion settings
     */
    private $company_id;

    /**
     * Construct a new CurrencyFormat object
     *
     * @param int $company_id The ID of the company to work off of
     * @see CurrencyFormat::setCompany()
     */
    public function __construct($company_id = null)
    {
        // Set the company given
        $this->setCompany($company_id);

        // Load the Currencies model so we can issue currency format actions
        Loader::loadModels($this, ['Currencies']);
    }

    /**
     * Set the company ID to work off of
     *
     * @param int $company_id The ID of the company to work off of
     * @return int Returns the ID of the previously set company ID (null of none set)
     */
    public function setCompany($company_id)
    {
        $cur_company = $this->company_id;
        if ($company_id) {
            $this->company_id = $company_id;
        }

        return $cur_company;
    }

    /**
     * Formats the float value into a currency string. Conversions are cached
     * to provide increased performance. To clear cache see CurrencyFormat::clear()
     *
     * @param float $value The decimal value to convert
     * @param string $currency The ISO 4217 currency code format to apply to $value
     * @param array $options An array of options to control the format (optional), if not set
     *   the options are pulled from the company settings. Options include:
     *
     *  - prefix Whether or not to include the prefix symbol (if one exists) true/false default true
     *  - suffix Whether or not to include the suffix symbold (if one exists) true/false default true
     *  - code Whether or not to include the currency code in the
     *      result (if one exists) true/false default company setting
     *  - with_separator Whether or not to include the separator (, or sometimes . or space) in the
     *      result true/false default true
     *  - html_code Whether or not to wrap the currency code with HTML true/false (default false).
     *      If true, will produce currency code like <span class="currency_code">USD</span>
     *  - decimals The number of decimal places (precision) (optional, default null to use the currency's
     *      defined precision)
     * @return string The formatted value
     * @see CurrencyFormat::cast()
     * @see CurrencyFormat::clear()
     */
    public function format($value, $currency, array $options = null)
    {
        // Currency should be a 3-character string
        if (!is_string($currency) || strlen($currency) != 3) {
            return $value;
        }

        static $use_code = null;

        if ($use_code == null) {
            Loader::loadComponents($this, ['SettingsCollection']);

            // Fetch the setting
            $use_code_setting = $this->SettingsCollection->fetchSetting(null, $this->company_id, 'show_currency_code');

            // Set whether or not to use codes
            $use_code = isset($use_code_setting['value']) && $use_code_setting['value'] == 'true' ? true : false;

            unset($use_code_setting);
        }

        // Default options
        $default_options = [
            'prefix' => true,
            'suffix' => true,
            'with_separator' => true,
            'code' => $use_code,
            'html_code' => false,
            'decimals' => null
        ];

        // Merge default options and custom user options
        $options = array_merge((array)$default_options, (array)$options);

        $html_wrap_code = false;
        if ($options['code'] && $options['html_code']) {
            $html_wrap_code = true;
            $options['code'] = false;
        }

        // Return the formatted value
        $value = $this->Currencies->toCurrency(
            $value,
            $currency,
            $this->company_id,
            $options['prefix'],
            $options['suffix'],
            $options['code'],
            $options['with_separator'],
            $options['decimals']
        );

        // Wrap the currency code in a span tag
        if ($html_wrap_code) {
            $value .= ' <span class=\"currency_code\">' . $currency . '</span>';
        }

        if ($options['html_code']) {
            $value = '<span class="currency_value">' . $value . '</span>';
        }

        return $value;
    }

    /**
     * Casts the currency string into a float value. Conversions are cached to
     * provide increased performance. To clear cache see CurrencyFormat::clear()
     *
     * @param string $value A currency value
     * @param string $currency The ISO 4217 currency code format representing $value
     * @param int $decimals The number of decimal places (precision) (optional, default null to use the currency's
     *  defined precision)
     * @return string The value in decimal format based on ISO 31-0
     * @see CurrencyFormat::format()
     * @see CurrencyFormat::clear()
     */
    public function cast($value, $currency, $decimals = null)
    {
        return $this->Currencies->toDecimal($value, $currency, $this->company_id, $decimals);
    }

    /**
     * Removes trailing zeros from a string preserving $min decimal places
     *
     * @param string $value The decimal value in string format
     * @param int $min The minimum number of decimal places
     * @param char $decimal_char The character used in $value for decimal representation
     * @retrun string The format decimal value with trailing zeros removed up to the $min value
     */
    public function truncateDecimal($value, $min, $decimal_char = '.')
    {
        return $this->Currencies->truncateDecimal($value, $min, $decimal_char);
    }

    /**
     * Clears the currency format cache. Useful if you made modifications to the
     * currency settings and need to continue conversion using those new
     * settings.
     */
    public function clear()
    {
        unset($this->Currencies);

        // Load the Currencies model so we can issue currency format actions
        Loader::loadModels($this, ['Currencies']);
    }
}
