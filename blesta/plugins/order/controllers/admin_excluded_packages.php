<?php
/**
 * Admin Excluded Packages controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order.controllers
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminExcludedPackages extends OrderAffiliateController
{
    /**
     * Pre Action
     */
    public function preAction()
    {
        parent::preAction();

        $this->requireLogin();

        $this->uses([
            'Order.OrderAffiliateCompanySettings',
            'Packages'
        ]);

        // Restore structure view location of the admin portal
        $this->structure->setDefaultView(APPDIR);
        $this->structure->setView(null, $this->orig_structure_view);

        Language::loadLang('admin_excluded_packages', null, PLUGINDIR . 'order' . DS . 'language' . DS);
    }

    /**
     * List available packages on the system
     */
    public function index()
    {
        // Get list of all active packages
        $packages = $this->Packages->getAll($this->company_id, ['name' => 'ASC'], 'active', null, ['hidden' => 1]);

        $hidden_packages = [];
        foreach ($packages as $key => $package) {
            $package->name = '';

            foreach ($package->names as $package_name) {
                if ($package_name->lang == Configure::get('Blesta.language')) {
                    $package->name = $package_name->name;
                }
            }

            if ($package->hidden == 1) {
                unset($packages[$key]);
                $hidden_packages[$key] = $package;
            } else {
                $packages[$key] = $package;
            }

        }

        $settings = $this->OrderAffiliateCompanySettings->getSetting($this->company_id, 'excluded_packages');
        $vars = isset($settings->value) ? (array)unserialize($settings->value) : [];

        if (!empty($this->post)) {
            $vars = (array)$this->post;
            $this->OrderAffiliateCompanySettings->setSetting($this->company_id, 'excluded_packages', serialize($vars));

            $this->flashMessage(
                'message',
                Language::_('AdminExcludedPackages.!success.packages_updated', true),
                null,
                false
            );
            $this->redirect($this->base_uri . 'plugin/order/admin_excluded_packages/');
        }

        $this->set('vars', $vars);
        $this->set('packages', $packages);
        $this->set('hidden_packages', $hidden_packages);
    }
}
