<?php
namespace Blesta\Core\Util\DataFeed\Feeds;

use Blesta\Core\Util\DataFeed\Common\AbstractDataFeed;
use Blesta\Core\Util\Input\Fields\InputFields;
use Configure;
use Language;
use Loader;

/**
 * Package feed
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed.Feeds
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PackageFeed extends AbstractDataFeed
{
    /**
     * @var array An array of options
     */
    private $options = [];

    /**
     * Initialize package feed
     */
    public function __construct()
    {
        parent::__construct();

        // Autoload the language file
        Language::loadLang(
            'package_feed',
            $this->options['language'] ?? Configure::get('Blesta.language'),
            COREDIR . 'Util' . DS . 'DataFeed' . DS . 'Feeds' . DS . 'language' . DS
        );
    }

    /**
     * Returns the name of the data feed
     *
     * @return string The name of the data feed
     */
    public function getName()
    {
        return Language::_('PackageFeed.name', true);
    }

    /**
     * Returns the description of the data feed
     *
     * @return string The description of the data feed
     */
    public function getDescription()
    {
        return Language::_('PackageFeed.description', true);
    }

    /**
     * Executes and returns the result of a given endpoint
     *
     * @param string $endpoint The endpoint to execute
     * @param array $vars An array containing the feed parameters
     * @return mixed The data feed response
     */
    public function get($endpoint, array $vars = [])
    {
        switch ($endpoint) {
            case 'name':
                return $this->nameEndpoint($vars);
            case 'description':
                return $this->descriptionEndpoint($vars);
            case 'pricing':
                return $this->pricingEndpoint($vars);
            default:
                return Language::_('PackageFeed.!error.invalid_endpoint', true);
        }
    }

    /**
     * Sets options for the data feed
     *
     * @param array $options An array of options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * Gets a list of the options input fields
     *
     * @param array $vars An array containing the posted fields
     * @return InputFields An object representing the list of input fields
     */
    public function getOptionFields(array $vars = [])
    {
        $fields = new InputFields();

        $base_url = 'http' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '')
            . '://' . Configure::get('Blesta.company')->hostname . WEBDIR;
        $fields->setHtml('
            <div class="title_row"><h3>' . Language::_('PackageFeed.getOptionFields.title_row_example_code', true) . '</h3></div>
            <div class="pad">
                <small>' . Language::_('PackageFeed.getOptionFields.example_code_name', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/package/name/?package_id=4&lang=en_us"&gt;&lt;/script&gt;</pre>
                
                <small>' . Language::_('PackageFeed.getOptionFields.example_code_description', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/package/description/?package_id=4&format=html&lang=en_us"&gt;&lt;/script&gt;</pre>
                
                <small>' . Language::_('PackageFeed.getOptionFields.example_code_pricing', true) . '</small>
                <pre class="rounded bg-light text-secondary border border-secondary p-2 m-0 my-1">&lt;script src="' . $base_url . 'feed/package/pricing/?pricing_id=4&get=price"&gt;&lt;/script&gt;</pre>
            </div>
        ');

        return $fields;
    }

    /**
     * Gets the name of a package in the given language
     *
     * @param array $vars An array containing the following items:
     *
     *  - package_id The ID of the package to fetch the name
     *  - lang The language in which to fetch the name (optional)
     */
    private function nameEndpoint(array $vars)
    {
        Loader::loadModels($this, ['Packages']);

        if (!isset($vars['lang'])) {
            $vars['lang'] = $this->options['language'] ?? Configure::get('Blesta.language');
        }

        // Get package
        $package = $this->Packages->get($vars['package_id'] ?? null);
        if (($errors = $this->Packages->errors())) {
            $this->setErrors($errors);

            return;
        }

        // Get package name
        if ($package) {
            Loader::loadHelpers($this, ['DataStructure']);
            $this->ArrayHelper = $this->DataStructure->create('Array');

            $names = $this->ArrayHelper->numericToKey($package->names, 'lang', 'name');

            return htmlentities($names[$vars['lang']] ?? $package->name);
        }
    }

    /**
     * Gets the description of a package in the given format and language
     *
     * @param array $vars An array containing the following items:
     *
     *  - package_id The ID of the package to fetch the name
     *  - lang The language in which to fetch the name (optional)
     *  - format In what format the data should be returned, it can be 'html' or 'text' (optional, default html)
     */
    private function descriptionEndpoint(array $vars)
    {
        Loader::loadModels($this, ['Packages']);

        if (!isset($vars['lang'])) {
            $vars['lang'] = $this->options['language'] ?? Configure::get('Blesta.language');
        }

        if (!isset($vars['format'])) {
            $vars['format'] = 'html';
        }

        // Get package
        $package = $this->Packages->get($vars['package_id'] ?? null);
        if (($errors = $this->Packages->errors())) {
            $this->setErrors($errors);

            return;
        }

        // Get package description
        if ($package) {
            Loader::loadHelpers($this, ['DataStructure']);
            $this->ArrayHelper = $this->DataStructure->create('Array');

            $descriptions = $this->ArrayHelper->numericToKey($package->descriptions, 'lang');

            return htmlentities(
                ($vars['format'] == 'text')
                    ? nl2br($descriptions[$vars['lang']]->{$vars['format']} ?? $package->description)
                    : ($descriptions[$vars['lang']]->{$vars['format']} ?? $package->description_html)
            );
        }
    }

    /**
     * Gets the price, renewing price, fees, currency, term and period of a pricing
     *
     * @param array $vars An array containing the following items:
     *
     *  - pricing_id The ID of the pricing to fetch the price
     *  - get The price to be obtained, it can be 'price', 'price_renews', 'price_transfer', 'setup_fee',
     *     'cancel_fee', 'currency', 'term' or 'period' (optional, default price)
     */
    private function pricingEndpoint(array $vars)
    {
        Loader::loadModels($this, ['Services']);
        Loader::loadHelpers($this, ['CurrencyFormat']);

        if (!isset($vars['get'])) {
            $vars['get'] = 'price';
        }

        // Get package pricing
        $pricing = $this->Services->getPackagePricing($vars['pricing_id'] ?? null);
        if (($errors = $this->Services->errors())) {
            $this->setErrors($errors);

            return;
        }

        // Get pricing price
        if ($pricing) {
            Loader::loadHelpers($this, ['DataStructure']);
            $this->ArrayHelper = $this->DataStructure->create('Array');

            $accepted_values = [
                'price', 'price_renews', 'price_transfer', 'setup_fee',
                'cancel_fee', 'currency', 'term', 'period'
            ];
            $monetary_values = [
                'price', 'price_renews', 'price_transfer',
                'setup_fee', 'cancel_fee'
            ];

            if (in_array($vars['get'], $accepted_values)) {
                if (in_array($vars['get'], $monetary_values)) {
                    return htmlentities($this->CurrencyFormat->format($pricing->{$vars['get']} ?? 0.00, $pricing->currency));
                } else {
                    return htmlentities($pricing->{$vars['get']} ?? '');
                }
            }
        }
    }
}