<?php

namespace Blesta\Core\Util\Tax;

use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Tax\Common\AbstractTax;
use Blesta\Pricing\Modifier\TaxPrice;
use Configure;
use Language;
use Loader;

/**
 * United Kingdom Tax
 *
 * @package blesta
 * @subpackage blesta.core.Util.Tax
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class UnitedKingdomTax extends AbstractTax
{
    /**
     * @var string The language of the client
     */
    private $language;

    /**
     * @var string The API base URL
     */
    private $endpoint = 'https://api.service.hmrc.gov.uk';

    /**
     * Gets the name of the tax provider
     *
     * @return string The name of the tax provider
     */
    public function getName()
    {
        // Load language file
        $this->language = !empty($this->language) ? $this->language : Configure::get('Blesta.language');
        Language::loadLang(
            'united_kingdom_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        return Language::_('Util.Tax.united_kingdom_tax.tax_provider_name', true);
    }

    /**
     * Gets a list of input fields for this tax provider
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields
     * @return InputFields An object representing the list of filter input field
     */
    public function getFields(array $vars = [])
    {
        // Autoload the language file
        $this->language = !empty($this->language) ? $this->language : Configure::get('Blesta.language');
        Language::loadLang(
            'united_kingdom_tax',
            $vars['language'] ?? $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set Enable UK VAT Validation field
        $enable_uk_vat = $fields->label('');
        $enable_uk_vat->attach(
            $fields->fieldCheckbox(
                'enable_uk_vat',
                'true',
                ($vars['enable_uk_vat'] ?? 'false') == 'true',
                ['id' => 'enable_uk_vat'],
                $fields->label(Language::_('Util.Tax.united_kingdom_tax.fields.enable_uk_vat', true), 'enable_uk_vat')
            )
        );
        $tooltip = $fields->tooltip(Language::_('Util.Tax.united_kingdom_tax.fields.note_enable_uk_vat', true));
        $enable_uk_vat->attach($tooltip);
        $fields->setField($enable_uk_vat);


        // Set Tax Exempt field
        $tax_exempt_uk_vat = $fields->label('');
        $tax_exempt_uk_vat->attach(
            $fields->fieldCheckbox(
                'tax_exempt_uk_vat',
                'true',
                ($vars['tax_exempt_uk_vat'] ?? 'false') == 'true',
                ['id' => 'tax_exempt_uk_vat'],
                $fields->label(Language::_('Util.Tax.united_kingdom_tax.fields.tax_exempt_uk_vat', true), 'tax_exempt_uk_vat')
            )
        );
        $tooltip = $fields->tooltip(Language::_('Util.Tax.united_kingdom_tax.fields.note_tax_exempt_uk_vat', true));
        $tax_exempt_uk_vat->attach($tooltip);
        $fields->setField($tax_exempt_uk_vat);


        // Set Intra-EU field
        $tax_intra_eu_uk_vat = $fields->label('');
        $tax_intra_eu_uk_vat->attach(
            $fields->fieldCheckbox(
                'tax_intra_eu_uk_vat',
                'true',
                ($vars['tax_intra_eu_uk_vat'] ?? 'false') == 'true',
                ['id' => 'tax_intra_eu_uk_vat'],
                $fields->label(Language::_('Util.Tax.united_kingdom_tax.fields.tax_intra_eu_uk_vat', true), 'tax_intra_eu_uk_vat')
            )
        );
        $tooltip = $fields->tooltip(Language::_('Util.Tax.united_kingdom_tax.fields.note_tax_intra_eu_uk_vat', true));
        $tax_intra_eu_uk_vat->attach($tooltip);
        $fields->setField($tax_intra_eu_uk_vat);

        // Set HTMl
        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function () {
                    toggleUkVat();
                    $('#enable_uk_vat').change(toggleUkVat);
                    $('#enable_eu_vat').change(toggleUkVat);

                    function toggleUkVat() {
                        if ($('#enable_uk_vat').is(':checked')) {
                            $('#tax_exempt_uk_vat').prop('disabled', false);

                            if ($('#enable_eu_vat').is(':checked')) {
                                $('#tax_intra_eu_uk_vat').prop('disabled', false);
                            } else {
                                $('#tax_intra_eu_uk_vat').prop('disabled', true);
                            }
                        } else {
                            $('#tax_exempt_uk_vat').prop('disabled', true);
                            $('#tax_intra_eu_uk_vat').prop('disabled', true);
                        }
                    }

                    enableIntraEu();
                    $('#tax_intra_eu_uk_vat').change(enableIntraEu);

                    function enableIntraEu() {
                        if ($('#tax_intra_eu_uk_vat').is(':checked')) {
                            $('#tax_home_eu_vat').append($('<option>', {
                                value: 'GB',
                                text: '" . Language::_('Util.Tax.united_kingdom_tax.fields.country_uk', true) . "',
                                " . (($vars['tax_home_eu_vat'] ?? '') == 'GB' ? 'selected: 1' : '') . "
                            }));
                        } else {
                            $('#tax_home_eu_vat option[value=\"GB\"]').remove();
                        }
                    }
                });
            </script>
        ");

        return $fields;
    }

    /**
     * Gets the type of tax used in the country by the tax provider
     *
     * @return array The taxation type, it can be 'inclusive_calculated', 'inclusive' or 'exclusive'
     */
    public function getTaxType()
    {
        return ['inclusive', 'inclusive_calculated'];
    }

    /**
     * Fetches the name of the tax ID field
     *
     * @param stdClass $client An object representing the client to be taxed
     * @return string The name of the tax ID field
     */
    public function getTaxIdName($client = null)
    {
        // Load language file
        $this->language = $client->settings['language'] ?? Configure::get('Blesta.language');
        Language::loadLang(
            'united_kingdom_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        return Language::_('Util.Tax.united_kingdom_tax.tax_id_name', true);
    }

    /**
     * Makes a request to the HMRC API
     *
     * @param string $path The API path to call
     * @param array $params The query parameters
     * @return stdClass The API response
     */
    private function makeRequest($path, array $params = [])
    {
        $url = $this->endpoint . '/' . ltrim($path, '/') . (isset($params) ? http_build_query($params) : '');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);

        if (!empty($response)) {
            $response = json_decode($response);
        }

        return $response;
    }

    /**
     * Verifies if a given tax ID is valid
     *
     * @param string $tax_id The tax ID to verify
     * @param array $client An array containing the client data associated with the tax ID
     * @return bool True if the given tax ID is valid, false otherwise
     */
    public function validateTaxId($tax_id, $client = null)
    {
        $country = isset($client['country']) ? $client['country'] : null;

        // Check if Intra-EU mode is enabled or not
        $tax_intra_eu = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'tax_intra_eu_uk_vat');

        if (
            isset($tax_intra_eu->value)
            && $tax_intra_eu->value == 'true'
            && $country !== 'GB'
            && (new EuropeTax())->isEnabled()
        ) {
            return (new EuropeTax())->validateTaxId($tax_id, $client);
        } else {
            $validator = $this->makeRequest('/organisations/vat/check-vat-number/lookup/' . $tax_id);

            return ($validator->target->vatNumber ?? '') == $tax_id;
        }

        return false;
    }

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
    public function getTaxInformation($tax_id, $client = null)
    {
        Loader::loadModels($this, ['Companies']);

        $country = $client['country'] ?? null;

        // Check if Intra-EU mode is enabled or not
        $tax_intra_eu = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'tax_intra_eu_uk_vat');

        if (
            isset($tax_intra_eu->value)
            && $tax_intra_eu->value == 'true'
            && $country !== 'GB'
            && (new EuropeTax())->isEnabled()
        ) {
            return (new EuropeTax())->getTaxInformation($tax_id, $client);
        } else {
            $taxpayer = $this->makeRequest('/organisations/vat/check-vat-number/lookup/' . $tax_id);
        }

        if (in_array($country, $this->getCountries())) {
            if (empty($tax_id)) {
                // The taxpayer is an individual
                return [
                    'name' => ($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''),
                    'address' => $client['address1'] ?? '',
                    'country' => $country,
                    'type' => 'individual',
                    'tax_id' => $tax_id,
                    'tax_exempt' => false
                ];
            } else {
                // The taxpayer is a business
                $tax_exempt = false;

                // Set tax exemption if Intra-EU mode is disabled
                if ($this->validateTaxId($tax_id, $client)) {
                    $tax_exempt = true;
                }

                return [
                    'name' => ($taxpayer->target->name ?? ''),
                    'address' => ($taxpayer->target->address->line1 ?? '') . ' '
                        . ($taxpayer->target->address->postcode ?? ''),
                    'country' => ($taxpayer->target->address->countryCode ?? ''),
                    'type' => 'business',
                    'tax_id' => ($taxpayer->target->vatNumber ?? ''),
                    'tax_exempt' => $tax_exempt
                ];
            }
        }

        return [
            'name' => ($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? ''),
            'address' => $client['address1'] ?? '',
            'country' => $client['country'] ?? '',
            'type' => (empty($client['company']) ? 'individual' : 'business'),
            'tax_id' => $tax_id,
            'tax_exempt' => true
        ];
    }

    /**
     * Gets a list of the countries where these tax requirements apply
     *
     * @return array A list containing the country codes in ISO 3166-1 alpha2
     */
    public function getCountries()
    {
        Loader::loadModels($this, ['Companies']);

        $tax_intra_eu_uk_vat = $this->Companies->getSetting(
            Configure::get('Blesta.company_id'),
            'tax_intra_eu_uk_vat'
        );
        if (
            isset($tax_intra_eu_uk_vat->value)
            && $tax_intra_eu_uk_vat->value == 'true'
            && (new EuropeTax())->isEnabled()
        ) {
            return array_merge((new EuropeTax())->getCountries(), ['GB']);
        }

        return ['GB'];
    }

    /**
     * Checks if the tax provider is enabled or not
     *
     * @param int $company_id The ID of the company to check (optional)
     * @return bool True if the tax provider is enabled
     */
    public function isEnabled($company_id = null)
    {
        Loader::loadModels($this, ['Companies']);

        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $enabled = $this->Companies->getSetting($company_id, 'enable_uk_vat');

        return isset($enabled->value) && $enabled->value == 'true';
    }

    /**
     * Checks if the tax provider will automatically handle TAX exemptions or not
     *
     * @param int $company_id The ID of the company to check (optional)
     * @return bool True if the tax provider will handle TAX exemptions
     */
    public function isExemptionHandlerEnabled($company_id = null)
    {
        Loader::loadModels($this, ['Companies']);

        if (is_null($company_id)) {
            $company_id = Configure::get('Blesta.company_id');
        }

        $enabled = $this->Companies->getSetting($company_id, 'tax_exempt_uk_vat');

        return isset($enabled->value) && $enabled->value == 'true' && $this->isEnabled();
    }

    /**
     * Gets the invoice notes from the tax provider
     *
     * @param stdClass $invoice
     * @return array A list of notes from the tax provider
     */
    public function getNotes($invoice)
    {
        Loader::loadModels($this, ['Clients']);
        $client = $this->Clients->get($invoice->client_id);
        return $this->reverseChargeApplies($client) ? [$this->getReverseChargeNote($client)] : [];
    }

    /**
     * Checks if the reverse charge applies for the given client with the tax provider
     *
     * @param stdClass $client An object representing the client to validate
     * @return bool True if the reverse charge principle applies to the given invoice
     */
    private function reverseChargeApplies($client)
    {
        $applies = isset($client)
            && $client->settings['tax_exempt'] == 'true'
            && $client->settings['enable_uk_vat'] == 'true'
            && !empty($client->settings['tax_id']);

        if ($client->settings['tax_intra_eu_uk_vat'] == 'true' && (new EuropeTax())->isEnabled()) {
            $applies = $applies
                && (
                    in_array($client->country, (new EuropeTax())->getCountries())
                        || in_array($client->country, $this->getCountries())
                );
        } else {
            $applies = $applies && in_array($client->country, $this->getCountries());
        }

        return $applies;
    }

    /**
     * Gets the reverse charge note for this tax provider
     *
     * @param stdClass $client An object representing the client to validate
     * @return string The reverse charge note
     */
    private function getReverseChargeNote($client)
    {
        Loader::loadHelpers($this, ['CurrencyFormat']);

        // Load language file
        $this->language = $client->settings['language'] ?? Configure::get('Blesta.language');
        Language::loadLang(
            'united_kingdom_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        $intra_eu = ($client->settings['tax_intra_eu_uk_vat'] ?? false)
            && ($client->country ?? '') !== 'GB';

        $term = 'Util.Tax.united_kingdom_tax.tax_note_domestic_reverse_charge';
        if ($intra_eu && (new EuropeTax())->isEnabled()) {
            $term = (new EuropeTax())->getReverseChargeNote($client);
        }

        return Language::_($term, true);
    }
}
