<?php
namespace Blesta\Module\BlestaLicense\Component;

use ModuleFields;
use Language;

class Package extends AbstractComponent
{
    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function add(array $vars = null)
    {
        // Set rules to validate input data
        $this->input->setRules($this->getRules($vars));

        // Build meta data to return
        $meta = array();
        if ($this->input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
            }
        }
        return $meta;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render
     *  as well as any additional HTML markup to include
     */
    public function fields($vars = null)
    {
        // Fetch the license pricing
        $pricings = $this->getPricings();

        $fields = new ModuleFields();

        // Set the license name
        $pricing_id = $fields->label(
            Language::_("Package.package_fields.pricing_id", true),
            "blesta_license_pricing_id"
        );
        $pricing_id->attach(
            $fields->fieldSelect(
                "meta[pricing_id]",
                $this->Html->ifSet($pricings, array()),
                $this->Html->ifSet($vars->meta['pricing_id']),
                array('id'=>"blesta_license_pricing_id")
            )
        );
        $fields->setField($pricing_id);

        return $fields;
    }

    /**
     * Initialize the license API
     *
     * @return \Blesta\ResellerApi\Command\Licenses
     */
    protected function licensePackages()
    {
        return $this->factory->create('Packages', $this->connection);
    }

    /**
     * Retrieves a formatted list of package pricings
     *
     * @return array A key/value array of pricings
     */
    private function getPricings()
    {
        $packages = $this->licensePackages()->get();
        $pricings = array();

        if (($packs = $packages->response()) && is_array($packs)) {
            foreach ($packs as $package) {
                foreach ($package->pricing as $pricing) {
                    $pricings[$pricing->id] = Language::_(
                        "Package.getpricings.pricing_id",
                        true,
                        $package->name,
                        $pricing->id
                    );
                }
            }
        }

        return $pricings;
    }

    /**
     * Retrieves rules for validating the addition of a package
     *
     * @param array An array of key/value pairs used to add the package
     * @return array An array of package rules
     */
    private function getRules(array $vars = null)
    {
        return array(
            'meta[pricing_id]' => array(
                'format' => array(
                    'rule' => array("matches", "/^[0-9]+$/"),
                    'message' => Language::_("Package.!error.meta[pricing_id].format", true)
                )
            )
        );
    }
}
