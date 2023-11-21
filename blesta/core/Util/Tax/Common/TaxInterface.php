<?php

namespace Blesta\Core\Util\Tax\Common;

/**
 * Tax interface
 *
 * @package blesta
 * @subpackage blesta.core.Util.Tax.Common
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface TaxInterface
{
    /**
     * Gets the name of the tax provider
     *
     * @return string The name of the tax provider
     */
    public function getName();

    /**
     * Gets a list of input fields for this tax provider
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields
     * @return InputFields An object representing the list of filter input field
     */
    public function getFields(array $vars = []);

    /**
     * Gets a list of the countries where these tax requirements apply
     *
     * @return array A list containing the country codes in ISO 3166-1 alpha2
     */
    public function getCountries();

    /**
     * Gets the tax information of a given tax ID
     *
     * @param string $tax_id The tax ID from which to obtain the information
     * @param array $client An array containing the client data associated with the tax ID
     * @return array An array containing the tax data
     *
     *  - name The name of the taxpayer
     *  - address The address of the taxpayer
     *  - country The country of the taxpayer
     *  - state The state/province of the taxpayer
     *  - type Whether the taxpayer is an individual or a business
     *  - tax_id The formatted tax ID
     *  - tax_exempt True if the taxpayer is exempt from taxes, false otherwise
     *  - settings A list of additional parameters (optional)
     */
    public function getTaxInformation($tax_id, $client = null);

    /**
     * Verifies if a given tax ID is valid
     *
     * @param string $tax_id The tax ID to verify
     * @param array $client An array containing the client data associated with the tax ID
     * @return bool True if the given tax ID is valid, false otherwise
     */
    public function validateTaxId($tax_id, $client = null);

    /**
     * Checks if the tax provider is enabled or not
     *
     * @param int $company_id The ID of the company to check (optional)
     * @return bool True if the tax provider is enabled
     */
    public function isEnabled($company_id = null);

    /**
     * Checks if the tax provider will automatically handle TAX exemptions or not
     *
     * @param int $company_id The ID of the company to check (optional)
     * @return bool True if the tax provider will handle TAX exemptions
     */
    public function isExemptionHandlerEnabled($company_id = null);
}
