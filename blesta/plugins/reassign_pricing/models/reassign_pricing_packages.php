<?php
/**
 * ReassignPricingPackages model
 *
 * @package blesta
 * @subpackage blesta.plugins.reassign_pricing
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ReassignPricingPackages extends ReassignPricingModel
{
    /**
     * Retrieves a list of packages that are compatible with the given module
     *
     * @param int $module_id The ID of the module whose packgaes to fetch
     */
    public function getCompatible($module_id)
    {
        Loader::loadModels($this, ['Packages']);

        $packages = $this->Packages->getAll(
            Configure::get('Blesta.company_id'),
            ['name' => 'ASC'],
            null,
            null,
            ['module_id' => $module_id]
        );

        foreach ($packages as &$package) {
            $package->pricing = $this->getPackagePricing($package->id);
        }

        return $packages;
    }

    /**
     * Fetches all pricing for the given package
     *
     * @param int $package_id The package ID to fetch pricing for
     * @return array An array of stdClass objects representing package pricing
     */
    private function getPackagePricing($package_id)
    {
        $fields = ['package_pricing.id', 'package_pricing.pricing_id', 'package_pricing.package_id',
            'pricings.term', 'pricings.period', 'pricings.price', 'pricings.setup_fee',
            'pricings.cancel_fee', 'pricings.currency'
        ];

        return $this->Record->select($fields)->from('package_pricing')
            ->innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)
            ->where('package_pricing.package_id', '=', $package_id)
            ->order(['period' => 'ASC', 'term' => 'ASC'])
            ->fetchAll();
    }
}
