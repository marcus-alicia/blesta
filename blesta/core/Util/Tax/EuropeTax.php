<?php

namespace Blesta\Core\Util\Tax;

use Blesta\Core\Util\Input\Fields\InputFields;
use Blesta\Core\Util\Tax\Common\AbstractTax;
use Blesta\Pricing\Modifier\TaxPrice;
use PH7\Eu\Vat\Validator;
use PH7\Eu\Vat\Provider\Europa;
use Configure;
use Language;
use Loader;

/**
 * European Union Tax
 *
 * @package blesta
 * @subpackage blesta.core.Util.Tax
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class EuropeTax extends AbstractTax
{
    /**
     * @var string The language of the client
     */
    private $language;

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
            'europe_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        return Language::_('Util.Tax.europe_tax.tax_provider_name', true);
    }

    /**
     * Gets a list of input fields for this tax provider
     *
     * @param array $vars A list of submitted inputs that act as defaults for filter fields
     * @return InputFields An object representing the list of filter input field
     */
    public function getFields(array $vars = [])
    {
        Loader::loadModels($this, ['Countries']);
        Loader::loadHelpers($this, ['Form']);

        // Autoload the language file
        $this->language = !empty($this->language) ? $this->language : Configure::get('Blesta.language');
        Language::loadLang(
            'europe_tax',
            $vars['language'] ?? $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        $fields = new InputFields();

        // Set Enable EU VAT Validation field
        $enable_eu_vat = $fields->label('');
        $enable_eu_vat->attach(
            $fields->fieldCheckbox(
                'enable_eu_vat',
                'true',
                ($vars['enable_eu_vat'] ?? 'false') == 'true',
                ['id' => 'enable_eu_vat'],
                $fields->label(Language::_('Util.Tax.europe_tax.fields.enable_eu_vat', true), 'enable_eu_vat')
            )
        );
        $tooltip = $fields->tooltip(Language::_('Util.Tax.europe_tax.fields.note_enable_eu_vat', true));
        $enable_eu_vat->attach($tooltip);
        $fields->setField($enable_eu_vat);


        // Set Tax Exempt field
        $tax_exempt_eu_vat = $fields->label('');
        $tax_exempt_eu_vat->attach(
            $fields->fieldCheckbox(
                'tax_exempt_eu_vat',
                'true',
                ($vars['tax_exempt_eu_vat'] ?? 'false') == 'true',
                ['id' => 'tax_exempt_eu_vat'],
                $fields->label(Language::_('Util.Tax.europe_tax.fields.tax_exempt_eu_vat', true), 'tax_exempt_eu_vat')
            )
        );
        $tooltip = $fields->tooltip(Language::_('Util.Tax.europe_tax.fields.note_tax_exempt_eu_vat', true));
        $tax_exempt_eu_vat->attach($tooltip);
        $fields->setField($tax_exempt_eu_vat);


        // Set Home Country field
        $countries = array_intersect_key(
            $this->Form->collapseObjectArray($this->Countries->getList(), 'name', 'alpha2'),
            array_flip($this->getCountries())
        );
        $tax_home_eu_vat = $fields->label(
            Language::_('Util.Tax.europe_tax.fields.tax_home_eu_vat', true),
            'tax_home_eu_vat'
        );
        $tax_home_eu_vat->attach(
            $fields->fieldSelect(
                'tax_home_eu_vat',
                ['' => Language::_('AppController.select.please', true)] + $countries,
                $vars['tax_home_eu_vat'] ?? null,
                ['id' => 'tax_home_eu_vat']
            )
        );
        $fields->setField($tax_home_eu_vat);

        // Set HTMl
        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function () {
                    toggleEuVat();
                    $('#enable_eu_vat').change(toggleEuVat);

                    function toggleEuVat() {
                        if ($('#enable_eu_vat').is(':checked')) {
                            $('#tax_exempt_eu_vat').prop('disabled', false);
                            $('#tax_home_eu_vat').prop('disabled', false);
                        } else {
                            $('#tax_exempt_eu_vat').prop('disabled', true);
                            $('#tax_home_eu_vat').prop('disabled', true);
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
            'europe_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        return Language::_('Util.Tax.europe_tax.tax_id_name', true);
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
        $country = $client['country'] ?? null;

        if ($country == 'GR') {
            $country = 'EL';
        }

        if (in_array($country, $this->getCountries())) {
            try {
                $validator = new Validator(new Europa, $tax_id, $country == 'GR' ? 'EL' : $country);

                return $validator->check();
            } catch (\Throwable $e) {
                $this->errors[] = ['valid' => $e->getMessage()];
            }
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

        if ($country == 'GR') {
            $country = 'EL';
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
                try {
                    $validator = new Validator(new Europa, $tax_id, $country == 'GR' ? 'EL' : $country);

                    $tax_home = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'tax_home_eu_vat');
                    $tax_home = $tax_home->value ?? null;

                    $tax_exempt = false;
                    if ($validator->check() && $validator->getCountryCode() !== $tax_home) {
                        $tax_exempt = true;
                    }

                    return [
                        'name' => $validator->getName(),
                        'address' => $validator->getAddress(),
                        'country' => $validator->getCountryCode(),
                        'type' => 'business',
                        'tax_id' => $validator->getVatNumber(),
                        'tax_exempt' => $tax_exempt
                    ];
                } catch (\Throwable $e) {
                    $this->errors[] = ['valid' => $e->getMessage()];
                }
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
        return [
            'AT', 'BE', 'BG', 'CY', 'CZ',
            'DE', 'DK', 'EE', 'ES', 'FI',
            'FR', 'GR', 'EL', 'HU', 'HR',
            'IE', 'IT', 'LT', 'LU', 'LV',
            'MT', 'NL', 'PL', 'PT', 'RO',
            'SE', 'SI', 'SK'
        ];
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

        $enabled = $this->Companies->getSetting($company_id, 'enable_eu_vat');

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

        $enabled = $this->Companies->getSetting($company_id, 'tax_exempt_eu_vat');

        return isset($enabled->value) && $enabled->value == 'true' && $this->isEnabled();
    }

    /**
     * Gets the invoice notes from the tax provider
     *
     * @param stdClass $invoice The invoice for which to get notes
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
        if (isset($client)
            && $client->settings['tax_exempt'] == 'true'
            && in_array($client->country, $this->getCountries())
            && $client->country !== $client->settings['tax_home_eu_vat']
            && !empty($client->settings['tax_id'])
        ) {
            return $client->settings['enable_eu_vat'] == 'true'
                || ($client->settings['enable_uk_vat'] == 'true'
                    && $client->settings['tax_intra_eu_uk_vat'] == 'true'
                    && $client->settings['enable_eu_vat'] !== 'true'
                );
        } else {
            return false;
        }
    }

    /**
     * Gets the reverse charge note for this tax provider
     *
     * @param stdClass $client An object representing the client to validate
     * @return string The reverse charge note
     */
    private function getReverseChargeNote($client)
    {
        // Load language file
        $this->language = $client->settings['language'] ?? Configure::get('Blesta.language');
        Language::loadLang(
            'europe_tax',
            $this->language,
            COREDIR . 'Util' . DS . 'Tax' . DS . 'language' . DS
        );

        return Language::_('Util.Tax.europe_tax.tax_note_reverse_charge', true);
    }
}
