<?php

/**
 * Tax providers management
 *
 * @package blesta
 * @subpackage blesta.app.models
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TaxProviders extends AppModel
{
    /**
     * Initialize Taxes
     */
    public function __construct()
    {
        parent::__construct();
        Language::loadLang(['tax_providers']);
    }

    /**
     * Get an instance of the given tax provider
     *
     * @param string $tax_provider The name of the tax provider to initialize
     * @return \Blesta\Core\Util\Tax\Common\AbstractTax An instance of the tax provider or false if provider not exists
     */
    public function get($tax_provider)
    {
        $class = '\\Blesta\\Core\\Util\\Tax\\' . Loader::toCamelCase($tax_provider);

        if (class_exists($class)) {
            return new $class();
        }

        return false;
    }

    /**
     * Get an instance of the tax provider for a given country
     *
     * @param string $country_code The code of the country of the tax provider to initialize
     * @return \Blesta\Core\Util\Tax\Common\AbstractTax An instance of the tax provider or false if provider not exists
     */
    public function getByCountry($country_code)
    {
        $dir = opendir(COREDIR . 'Util' . DS . 'Tax');
        while (false !== ($tax = readdir($dir))) {
            // If the file is not a hidden file, and is not a directory, accept it
            if (substr($tax, 0, 1) != '.' && !is_dir(COREDIR . 'Util' . DS . 'Tax' . DS . $tax)) {
                try {
                    $class = '\\Blesta\\Core\\Util\\Tax\\' . rtrim($tax, '.php');

                    if (class_exists($class)) {
                        $countries = (new $class())->getCountries();

                        if (in_array($country_code, $countries)) {
                            return new $class();
                        }
                    }
                } catch (Exception $e) {
                    // The tax provider could not be loaded, try the next
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Get a list of all tax providers
     *
     * @param bool $enabled True to fetch only the enabled providers (default, true)
     * @return array A list of all tax providers
     */
    public function getAll($enabled = true)
    {
        $providers = [];

        $dir = opendir(COREDIR . 'Util' . DS . 'Tax');
        while (false !== ($tax = readdir($dir))) {
            // If the file is not a hidden file, and is not a directory, accept it
            if (substr($tax, 0, 1) != '.' && !is_dir(COREDIR . 'Util' . DS . 'Tax' . DS . $tax)) {
                try {
                    $class = '\\Blesta\\Core\\Util\\Tax\\' . rtrim($tax, '.php');

                    if (class_exists($class)) {
                        if (!(new $class())->isEnabled() && $enabled) {
                            continue;
                        }

                        $filename = rtrim($tax, '.php');
                        $providers[$filename] = new $class();
                    }
                } catch (Exception $e) {
                    // The tax provider could not be loaded, try the next
                    continue;
                }
            }
        }

        return $providers;
    }

    /**
     * Get a list of all countries handled by a tax provider
     *
     * @param string $tax_provider The name of the tax provider to fetch the countries list
     * @return array A list of all countries handled by a tax provider or false if provider not exists
     */
    public function getCountries($tax_provider)
    {
        $class = '\\Blesta\\Core\\Util\\Tax\\' . Loader::toCamelCase($tax_provider);

        if (class_exists($class)) {
            return (new $class())->getCountries();
        }

        return false;
    }

    /**
     * Get a list of all countries handled by all tax providers
     *
     * @param bool $enabled True to fetch only the countries from the enabled providers (default, true)
     * @param bool $exempt True to fetch only the countries with a tax exemption handler enabled (default, true)
     * @return array A list of all countries handled by all tax providers
     */
    public function getAllCountries($enabled = true, $exempt = true)
    {
        $countries = [];

        $dir = opendir(COREDIR . 'Util' . DS . 'Tax');
        while (false !== ($tax = readdir($dir))) {
            // If the file is not a hidden file, and is not a directory, accept it
            if (substr($tax, 0, 1) != '.' && !is_dir(COREDIR . 'Util' . DS . 'Tax' . DS . $tax)) {
                try {
                    $class = '\\Blesta\\Core\\Util\\Tax\\' . rtrim($tax, '.php');

                    if (class_exists($class)) {
                        if (!(new $class())->isEnabled() && $enabled) {
                            continue;
                        }

                        if (!(new $class())->isExemptionHandlerEnabled() && $exempt) {
                            continue;
                        }

                        $countries = array_merge($countries, (new $class())->getCountries());
                    }
                } catch (Exception $e) {
                    // The tax provider could not be loaded, try the next
                    continue;
                }
            }
        }

        return $countries;
    }

    /**
     * Checks if a tax provider is handling the tax exemption of clients
     *
     * @param string $tax_provider The name of the tax provider to check or null to check if any provider
     *  is enabled (default, null)
     * @return bool True if the tax provider exemption handler is enabled
     */
    public function isExemptionHandlerEnabled($tax_provider = null)
    {
        if (is_null($tax_provider)) {
            $providers = $this->getAll();

            foreach ($providers as $provider) {
                $enabled = $provider->isExemptionHandlerEnabled();

                if ($enabled) {
                    return $enabled;
                }
            }
        } else {
            $class = '\\Blesta\\Core\\Util\\Tax\\' . Loader::toCamelCase($tax_provider);

            if (class_exists($class)) {
                return (new $class())->isExemptionHandlerEnabled();
            }
        }

        return false;
    }
}
