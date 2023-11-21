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
class ReassignPricingServices extends ReassignPricingModel
{
    /**
     * Updates a service to reassign its pricing information
     *
     * @param int $service_id The ID of the service to update
     * @param array $vars A key/value array of service fields to update, including:
     *  - pricing_id The ID of the package's pricing to update to
     *  - package_group_id The ID of the package group to update to
     */
    public function edit($service_id, array $vars)
    {
        $vars['service_id'] = $service_id;
        $vars['module_row_id'] = $this->getPackageModuleRow((isset($vars['pricing_id']) ? $vars['pricing_id'] : null));

        $this->Input->setRules($this->getRules($vars));

        if ($this->Input->validates($vars)) {
            $this->Record->where('id', '=', $service_id)
                ->update('services', $vars, ['pricing_id', 'package_group_id', 'module_row_id']);
        }
    }

    /**
     * Retrieves the module row associated with the given package pricing
     *
     * @param int $pricing_id The package pricing ID
     * @return mixed The module row ID if it exists, or null otherwise
     */
    protected function getPackageModuleRow($pricing_id)
    {
        $package = $this->Record->select(['packages.module_row'])
            ->from('package_pricing')
            ->innerJoin('packages', 'packages.id', '=', 'package_pricing.package_id', false)
            ->where('package_pricing.id', '=', $pricing_id)
            ->fetch();

        return ($package ? $package->module_row : null);
    }

    /**
     * Validates that the given pricing belongs to the given company
     *
     * @param int $pricing_id The ID of the package pricing to check
     * @param int $company_id The ID of the company
     * @return bool True if the pricing belongs to the given company, or false otherwise
     */
    public function validatePricingCompany($pricing_id, $company_id)
    {
        $pricing = $this->Record->select(['pricings.*'])
            ->from('package_pricing')
            ->innerJoin('pricings', 'pricings.id', '=', 'package_pricing.pricing_id', false)
            ->where('package_pricing.id', '=', $pricing_id)
            ->fetch();

        if ($pricing && $pricing->company_id != $company_id) {
            return false;
        }

        return true;
    }

    /**
     * Validates that the given package group belongs to the given company
     *
     * @param int $package_group_id The ID of the package group to check
     * @param int $company_id The ID of the company
     * @return bool True if the package group belongs to the given company, or false otherwise
     */
    public function validatePackageGroupCompany($package_group_id, $company_id)
    {
        $package_group = $this->Record->select(['package_groups.*'])
            ->from('package_groups')
            ->where('package_groups.id', '=', $package_group_id)
            ->fetch();

        if ($package_group && $package_group->company_id != $company_id) {
            return false;
        }

        return true;
    }

    /**
     * Validates that the given module row belongs to the given company
     *
     * @param int $module_row_id The ID of the module row to check
     * @param int $company_id The ID of the company
     * @return bool True if the module row belongs to the given company, or false otherwise
     */
    public function validateModuleCompany($module_row_id, $company_id)
    {
        $module = $this->Record->select(['modules.*'])
            ->from('module_rows')
            ->innerJoin('modules', 'modules.id', '=', 'module_rows.module_id', false)
            ->where('module_rows.id', '=', $module_row_id)
            ->fetch();

        if ($module && $module->company_id != $company_id) {
            return false;
        }

        return true;
    }

    /**
     * Validates that the given module row belongs to the given company
     *
     * @param int $module_row_id The ID of the module row to check
     * @return bool True if the module row belongs to the given company, or false otherwise
     */
    public function validateModuleRow($module_row_id)
    {
        $module = $this->Record->select(['module_rows.*'])
            ->from('module_rows')
            ->where('module_rows.id', '=', $module_row_id)
            ->fetch();

        return ($module || $module_row_id === '0' || $module_row_id === 0);
    }

    /**
     * Retrieves the company ID of the client the service belongs to
     *
     * @param int $service_id The ID of the service
     * @return mixed The company ID, or null if unknown
     */
    protected function getServiceCompany($service_id)
    {
        $company = $this->Record->select(['client_groups.company_id'])
            ->from('services')
            ->innerJoin('clients', 'clients.id', '=', 'services.client_id', false)
            ->innerJoin('client_groups', 'client_groups.id', '=', 'clients.client_group_id', false)
            ->where('services.id', '=', $service_id)
            ->fetch();

        return (
            $company
            ? $company->company_id
            : null
        );
    }

    /**
     * Retrieves a set of rules to validate updating a service's pricing
     *
     * @param array $vars An array of input
     * @return array An array of Input validation rules
     */
    private function getRules(array $vars)
    {
        $company_id = $this->getServiceCompany((isset($vars['service_id']) ? $vars['service_id'] : null));

        return [
            'pricing_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_pricing'],
                    'message' => $this->_('ReassignPricingServices.!error.pricing_id.exists')
                ],
                'company' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePricingCompany'], $company_id],
                    'message' => $this->_('ReassignPricingServices.!error.pricing_id.company')
                ]
            ],
            'package_group_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'package_groups'],
                    'message' => $this->_('ReassignPricingServices.!error.package_group_id.exists')
                ],
                'company' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validatePackageGroupCompany'], $company_id],
                    'message' => $this->_('ReassignPricingServices.!error.package_group_id.company')
                ]
            ],
            'module_row_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateModuleRow']],
                    'message' => $this->_('ReassignPricingServices.!error.module_row_id.exists')
                ],
                'company' => [
                    'if_set' => true,
                    'rule' => [[$this, 'validateModuleCompany'], $company_id],
                    'message' => $this->_('ReassignPricingServices.!error.module_row_id.company')
                ]
            ],
            'service_id' => [
                'exists' => [
                    'rule' => [[$this, 'validateExists'], 'id', 'services'],
                    'message' => $this->_('ReassignPricingServices.!error.service_id.exists')
                ]
            ]
        ];
    }
}
