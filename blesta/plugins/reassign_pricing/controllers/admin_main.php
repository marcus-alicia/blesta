<?php
/**
 * Reassign Pricing Admin Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.reassign_pricing
 * @copyright Copyright (c) 2015, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends ReassignPricingController
{
    /**
     * Setup
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses(['Clients', 'Packages', 'Services']);
        $this->helpers(['CurrencyFormat']);

        // Require a valid client be given
        if (!isset($this->get[0]) || !($this->client = $this->Clients->get($this->get[0]))) {
            $this->redirect($this->base_uri);
        }

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        $page_title = Language::_('AdminMain.index.page_title', true, $this->Html->safe($this->client->id_code));
        $this->structure->set('page_title', $page_title);

        $this->staff_id = $this->Session->read('blesta_staff_id');
    }

    /**
     * List services
     */
    public function index()
    {
        $status = 'active';
        $page = (isset($this->get[1])
            ? (int)$this->get[1]
            : 1
        );
        $sort = (isset($this->get['sort'])
            ? $this->get['sort']
            : 'date_added'
        );
        $order = (isset($this->get['order'])
            ? $this->get['order']
            : 'desc'
        );

        // Fetch the services
        $services = $this->Services->getList($this->client->id, $status, $page, [$sort => $order]);
        $total_services = $this->Services->getListCount($this->client->id, $status);

        // Set the expected service renewal price
        foreach ($services as $service) {
            $service->renewal_price = $this->Services->getRenewalPrice($service->id);
        }

        $this->set('client', $this->client);
        $this->set('services', $services);
        $this->set('sort', $sort);
        $this->set('order', $order);
        $this->set('negate_order', ($order == 'asc' ? 'desc' : 'asc'));
        $this->set('periods', $this->getPeriods());

        $paginate_uri = $this->base_uri . 'plugin/reassign_pricing/admin_main/index/' . $this->client->id . '/[p]/';
        $this->setCurrentPagination($paginate_uri, $total_services, ['sort' => $sort, 'order' => $order]);
    }

    /**
     * Service page to reassign pricing
     */
    public function service()
    {
        // Require a valid service be given
        if (!isset($this->get[1]) || !($service = $this->Services->get($this->get[1]))
            || $this->client->id != $service->client_id) {
            $this->redirect($this->base_uri . 'plugin/reassign_pricing/admin_main/');
        }

        $this->setMessage('notice', Language::_('AdminMain.!warning.reassign_pricing', true), false, null, false);

        $this->uses(['Coupons', 'ReassignPricing.ReassignPricingServices']);

        // Update the service to set its new pricing
        if (!empty($this->post)) {
            $this->ReassignPricingServices->edit($service->id, $this->post);

            if (($errors = $this->ReassignPricingServices->errors())) {
                // Error, reset vars
                $this->setMessage('error', $errors, false, null, false);
                $vars = (object)$this->post;
            } else {
                // Success
                $this->flashMessage(
                    'message',
                    Language::_('AdminMain.!success.service_updated', true),
                    null,
                    false
                );
                $this->redirect(
                    $this->base_uri . 'plugin/reassign_pricing/admin_main/service/'
                    . $this->client->id . '/' . $service->id
                );
            }
        }

        // Set initial vars
        if (!isset($vars)) {
            $vars = (object)[
                'pricing_id' => $service->pricing_id,
                'package_group_id' => $service->package_group_id
            ];
        }

        // Determine whether a recurring coupon applies to this service
        $recurring_coupon = false;
        if ($service->coupon_id && $service->date_renews) {
            $recurring_coupon = $this->Coupons->getRecurring(
                $service->coupon_id,
                $service->package_pricing->currency,
                $service->date_renews . 'Z'
            );
        }

        $service->renewal_price = $this->Services->getRenewalPrice($service->id);

        $this->set('vars', $vars);
        $this->set('client', $this->client);
        $this->set('service', $service);
        $this->set('package_groups', $this->getPackageGroups($service->pricing_id));
        $this->set('terms', $this->getPackageTerms($service));
        $this->set('recurring_coupon', $recurring_coupon);
        $this->set('periods', $this->getPeriods());
        $this->set('statuses', $this->Services->getStatusTypes());
    }

    /**
     * Retrieves a list of package/terms to reassign the service pricing to
     *
     * @param stdClass $service An stdClass object representing the service
     * @return array A key/value array of package terms
     */
    protected function getPackageTerms(stdClass $service)
    {
        $this->uses(['ReassignPricing.ReassignPricingPackages']);

        // If no compatible packages are available, the package itself is the only compatible package
        $module_id = $this->getModuleId($service->module_row_id);
        $compatible_packages = $this->ReassignPricingPackages->getCompatible($module_id);
        $compatible_packages = (empty($compatible_packages) ? [$service->package] : $compatible_packages);

        $terms = [];
        foreach ($compatible_packages as $package) {
            $terms['package_' . $package->id] = ['name' => $package->name, 'value' => 'optgroup'];
            $terms = $terms + $this->formatPackageTerms($package);
        }

        return $terms;
    }

    /**
     * Retrieves a list of package group to reassign the service pricing to
     *
     * @param int $pricing_id The pricing ID by which to fetch pacage groups
     * @return array A key/value array of package groups
     */
    public function getPackageGroups($pricing_id = null)
    {
        $this->uses(['Packages']);
        $this->helpers(['Form']);

        $groups = [];
        if ((isset($this->get[1]) || $pricing_id)
            && ($package = $this->Packages->getByPricingId($pricing_id ? $pricing_id : $this->get[1]))
        ) {
            $groups = $this->Form->collapseObjectArray(
                $package->groups,
                'name',
                'id'
            );
        }

        if ($pricing_id === null) {
            echo json_encode(['groups' => $groups]);
        }

        return ($pricing_id === null ? false : $groups);
    }

    /**
     * Retrieves a set of package pricing periods
     *
     * @return array An array of package pricing periods
     */
    protected function getPeriods()
    {
        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        return $periods;
    }

    /**
     * Retrieves the associated module ID of the given module row
     *
     * @param int $module_row_id The ID of th emodule row whose module ID to retrieve
     * @return mixed The module ID if it exists, otherwise null
     */
    private function getModuleId($module_row_id)
    {
        if (!isset($this->ModuleManager)) {
            $this->uses(['ModuleManager']);
        }

        if (($module = $this->ModuleManager->getRow($module_row_id))) {
            return $module->module_id;
        } elseif ($module_row_id == 0 && ($module = $this->ModuleManager->getByClass('none'))) {
            return $module[0]->id;
        }

        return null;
    }

    /**
     * Returns an array of all pricing terms for the given package
     *
     * @param stdClass $package A stdClass object representing the package to fetch the terms for
     * @return array An array of key/value pairs where the key is the package pricing ID and the value
     *  is a string representing the price, term, and period.
     */
    private function formatPackageTerms(stdClass $package)
    {
        $singular_periods = $this->Packages->getPricingPeriods();
        $plural_periods = $this->Packages->getPricingPeriods(true);
        $terms = [];

        if (isset($package->pricing) && !empty($package->pricing)) {
            foreach ($package->pricing as $price) {
                $term = ($price->period == 'onetime'
                    ? 'AdminMain.service.term_onetime'
                    : 'AdminMain.service.term'
                );

                $terms[$price->id] = Language::_(
                    $term,
                    true,
                    $price->term,
                    $price->term != 1 ? $plural_periods[$price->period] : $singular_periods[$price->period],
                    $this->CurrencyFormat->format($price->price, $price->currency)
                );
            }
        }

        return $terms;
    }
}
