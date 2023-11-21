<?php
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * Domain Name Order Type
 *
 * @package blesta
 * @subpackage blesta.plugins.order.lib.order_types
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderTypeDomain extends OrderType
{
    /**
     * @var string The authors of this order type
     */
    private static $authors = [['name'=>'Phillips Data, Inc.','url'=>'http://www.blesta.com']];

    /**
     * Construct
     */
    public function __construct()
    {
        Language::loadLang('order_type_domain', null, dirname(__FILE__) . DS . 'language' . DS);

        Loader::loadComponents($this, ['Input']);
    }

    /**
     * Returns the name of this order type
     *
     * @return string The common name of this order type
     */
    public function getName()
    {
        return Language::_('OrderTypeDomain.name', true);
    }

    /**
     * Returns the name and URL for the authors of this order type
     *
     * @return array The name and URL of the authors of this order type
     */
    public function getAuthors()
    {
        return self::$authors;
    }

    /**
     * Create and return the view content required to modify the custom settings of this order form
     *
     * @param array $vars An array of order form data (including meta data unique to this form type)
     *  to be updated for this order form
     * @return string HTML content containing the fields to update the meta data for this order form
     */
    public function getSettings(array $vars = null)
    {
        $this->view = new View();
        $this->view->setDefaultView('plugins' . DS . 'order' . DS . 'lib' . DS . 'order_types' . DS . 'domain' . DS);
        $this->view->setView('settings', 'default');

        Loader::loadHelpers($this, ['Html', 'Form', 'Javascript']);

        Loader::loadModels($this, ['Packages', 'Companies', 'PluginManager', 'ModuleManager', 'Invoices']);

        // Fetch all available package groups
        $package_groups = $this->Form->collapseObjectArray(
            $this->Packages->getAllGroups(Configure::get('Blesta.company_id'), null, 'standard', ['hidden' => true]),
            'name',
            'id'
        );

        // Fetch all the available TLDs
        $tlds = [];
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            Loader::loadModels($this, ['Domains.DomainsTlds']);
            $tlds = $this->Form->collapseObjectArray(
                $this->DomainsTlds->getAll(),
                'tld',
                'tld'
            );
        } else {
            // Get all registrar modules
            $modules = $this->ModuleManager->getAll(
                Configure::get('Blesta.company_id'),
                'name',
                'asc',
                ['type' => 'registrar']
            );
            foreach ($modules as $module) {
                $module_tlds = $this->ModuleManager->moduleRpc($module->id, 'getTlds');
                if (is_array($module_tlds)) {
                    $tlds = array_merge($tlds, array_combine($module_tlds, $module_tlds));
                }
            }
        }

        if (array_key_exists('.', $tlds)) {
            unset($tlds['.']);
        }

        if (!empty($vars['meta']['tlds'])) {
            $tlds = array_diff($tlds, $vars['meta']['tlds']);
            $vars['meta']['tlds'] = array_combine($vars['meta']['tlds'], $vars['meta']['tlds']);
        }

        // Set the pricing periods
        $pricing_periods = $this->Invoices->getPricingPeriods();

        // If the "Domain Manager" plugin is installed, then default to it's package group for domain order forms
        $domain_package_group = $this->Companies->getSetting(
            Configure::get('Blesta.company_id'),
            'domains_package_group'
        );
        if (!isset($vars['meta']['domain_group']) && $domain_package_group) {
            $vars['meta']['domain_group'] = $domain_package_group->value;
        }

        // Get eligible package groups for a free domain
        $eligible_package_groups = $package_groups;
        if (isset($eligible_package_groups[$domain_package_group->value])) {
            unset($eligible_package_groups[$domain_package_group->value]);
        }

        if (!empty($vars['meta']['package_groups'])) {
            $vars['meta']['package_groups'] = array_intersect_key(
                $eligible_package_groups,
                array_flip($vars['meta']['package_groups'])
            );
            $eligible_package_groups = array_diff_key($eligible_package_groups, $vars['meta']['package_groups']);
        }

        $this->view->set('package_groups', $package_groups);
        $this->view->set('eligible_package_groups', $eligible_package_groups);
        $this->view->set('pricing_periods', $pricing_periods);
        $this->view->set('tlds', $tlds);

        $this->view->set('vars', (object)$vars);

        return $this->view->fetch();
    }

    /**
     * Validates the given data (settings) to be updated for this order form
     *
     * @param array $vars An array of order form data (including meta data unique to this form type)
     *  to be updated for this order form
     * @return array The order form data to be updated in the database for this order form,
     *  or reset into the form on failure
     */
    public function editSettings(array $vars)
    {
        Loader::loadComponents($this, ['Record']);

        $rules = [
            'template' => [
                'valid' => [
                    'rule' => [[$this, 'validateTemplate'], isset($vars['groups']) ? $vars['groups'] : null],
                    'message' => Language::_('OrderTypeDomain.!error.template.valid', true)
                ]
            ],
            'meta[domain_group]' => [
                'exists' => [
                    'rule' => function ($package_group) {
                        $count = $this->Record->select(['id'])
                            ->from('package_groups')
                            ->where('id', '=', $package_group)
                            ->numResults();
                        return ($count > 0);
                    },
                    'message' => Language::_('OrderTypeDomain.!error.meta[domain_group].exists', true)
                ],
                'groups' => [
                    'rule' => [
                        function ($package_group, $groups) {
                            // The package group is not set as a group if it's not an array
                            if (!is_array($groups)) {
                                return true;
                            }

                            return !in_array($package_group, $groups);
                        },
                        ['_linked' => 'groups']
                    ],
                    'message' => Language::_('OrderTypeDomain.!error.meta[domain_group].groups', true)
                ]
            ]
        ];
        $this->Input->setRules($rules);
        if ($this->Input->validates($vars)) {
            return $vars;
        }
    }

    /**
     * Verify that the given template is valid
     *
     * @param string $template The template type used
     * @param array $groups An array of package groups selected for the order form
     */
    public function validateTemplate($template, $groups)
    {
        if ($template == 'ajax' && empty($groups)) {
            return false;
        }
        return true;
    }

    /**
     * Ensures that all the data submitted for items is valid
     *
     * @param array $vars An array containing item details to validate
     * @return True if all the items are valid, false otherwise
     */
    public function validateItems($vars)
    {
        Loader::loadModels($this, ['Packages', 'ModuleManager']);

        // Check that the domain assigned to each pricing matches an available tld on the pricing package
        foreach ($vars['pricing_id'] ?? [] as $key => $pricing_id) {
            if (!$this->validateDomainPricing($key, $pricing_id)) {
                return false;
            }
        }

        // Make sure the domains submitted match the meta data submitted
        foreach ($vars['domains'] ?? [] as $domain) {
            if (!array_key_exists($domain, $vars['meta'] ?? [])) {
                return false;
            }
        }

        // Make sure that the meta array is consistent within itself
        foreach ($vars['meta'] ?? [] as $domain => $meta) {
            if (isset($meta['domain']) && ($domain != $meta['domain'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensures that the domain submitted has a valid term selected
     *
     * @param string $domain The domain name to validate
     * @param int $pricing_id The pricing ID of the term
     * @return bool if all the items are valid, false otherwise
     */
    public function validateTerm($domain, $pricing_id, $transfer = 'false')
    {
        Loader::loadModels($this, ['Packages', 'ModuleManager']);

        if (empty($pricing_id)) {
            return false;
        }

        $tld = $this->getTld($domain);
        $package = $this->Packages->getByPricingId($pricing_id);
        $term = 1;

        foreach ($package->pricing as $pricing) {
            if ($pricing->id == ($vars['pricing_id'][$domain] ?? null) && $pricing->period == 'year') {
                $term = $pricing->term;
            }
        }

        $valid_term = $this->ModuleManager->moduleRpc(
            $package->module_id,
            'isValidTerm',
            [$tld, $term, ($transfer ?? 'false') == 'true'],
            $package->module_row
        );
        $valid_term = is_null($valid_term) ? true : $valid_term;

        if (!$valid_term) {
            return false;
        }

        return true;
    }

    /**
     * Validates whether a domain is valid for the given pricing ID
     *
     * @param type $domain The domain to validate
     * @param type $pricing_id The pricing_id tp validate against
     * @return bool True if the domain is valid for the pricing_id, false otherwise
     */
    private function validateDomainPricing($domain, $pricing_id)
    {
        Loader::loadModels($this, ['Packages']);
        $domain_tld = substr($domain, strpos($domain, '.'));

        // Check if the domain tld is valid for this package
        $package = $this->Packages->getByPricingId($pricing_id);
        if ($package && isset($package->meta->tlds) && !in_array($domain_tld, $package->meta->tlds)) {
            return false;
        }

        return true;
    }

    /**
     * Determines whether or not the order type supports multiple package groups or just a single package group
     *
     * @return mixed If true will allow multiple package groups to be selected,
     *  false allows just a single package group, null will not allow package selection
     */
    public function supportsMultipleGroups()
    {
        return true;
    }

    /**
     * Sets the SessionCart being used by the order form
     *
     * @param SessionCart $cart The session cart being used by the order form
     */
    public function setCart(SessionCart $cart)
    {
        parent::setCart($cart);
        $this->cart->setCallback('prequeueItem', [$this, 'prequeueItem']);
        $this->cart->setCallback('addItem', [$this, 'addItem']);
    }

    /**
     * Determines whether or not the order type requires the perConfig step of
     * the order process to be invoked.
     *
     * @return bool If true will invoke the preConfig step before selecting a package,
     *  false to continue to the next step
     */
    public function requiresPreConfig()
    {
        return true;
    }

    /**
     * Handle an HTTP request. This allows an order template to execute custom code
     * for the order type being used, allowing tighter integration between the order type and the template.
     * This can be useful for supporting AJAX requests and the like.
     *
     * @param array $get All GET request parameters
     * @param array $post All POST request parameters
     * @param array $files All FILES request parameters
     * @param stdClass $order_form The order form currently being used
     * @return string HTML content to render (if any)
     */
    public function handleRequest(array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View();
        $this->view->setDefaultView('plugins' . DS . 'order' . DS);
        $this->view->setView(
            null,
            'templates' . DS . $this->order_form->template . DS . 'types' . DS . $this->order_form->type
        );

        // Load the helpers required for this view
        Loader::loadHelpers(
            $this,
            ['Form', 'Html', 'WidgetClient', 'CurrencyFormat' => [Configure::get('Blesta.company_id')]]
        );
        Loader::loadModels($this, ['Companies', 'PackageGroups']);
        Loader::loadComponents($this, ['Session']);

        $this->cart->setData('get_parameters', $get);
        $currency = $this->cart->getData('currency');
        $tlds = $this->getTlds();
        $domains = [];

        if (isset($post['domain'])) {
            if (!isset($post['tlds'])) {
                $post['tlds'] = [];
            }

            $post['domain'] = strtolower($post['domain']);

            $sld = $this->getSld($post['domain']);
            $tld = $this->getTld($post['domain']);

            if ($tld != '' && !in_array($tld, $post['tlds'])) {
                $post['tlds'][] = $tld;
            }

            foreach ($post['tlds'] as $tld) {
                $pack = $this->domainPackageGroup($sld . $tld, $tlds);
                if ($pack) {
                    $pack[0] = $this->updatePackagePricing($pack[0], $currency);

                    $domains[$sld . $tld] = new stdClass();
                    $domains[$sld . $tld]->package = $pack[0];
                    $domains[$sld . $tld]->group = $pack[1];
                }

                $post['domains'][] = $sld . $tld;
            }

            // If no packages found, nothing to do...
            if (empty($domains)) {
                $this->Input->setErrors([
                    'domain' => [
                        'invalid' => Language::_('OrderTypeDomain.!error.domain.invalid', true)
                    ]
                ]);
            } else {
                Loader::loadModels($this, ['ModuleManager']);

                if (isset($post['transfer'])) {
                    // Domain name blank
                    if (empty($post['domain'])) {
                        $this->Input->setErrors([
                            'domain' => [
                                'empty' => Language::_('OrderTypeDomain.!error.domain.empty', true)
                            ]
                        ]);
                        $domains = [];
                    }

                    // Check availability
                    $availability = [];
                    foreach ($domains as $domain => $pack) {
                        $availability[$domain] = $this->ModuleManager->moduleRpc(
                            $pack->package->module_id,
                            'checkTransferAvailability',
                            [$domain],
                            $pack->package->module_row
                        );

                        if (is_null($availability[$domain])) {
                            $availability[$domain] = true;
                        }
                    }

                    if (($errors = $this->ModuleManager->errors())) {
                        $this->Input->setErrors($errors);
                    }
                } else {
                    // Check availability
                    $availability = [];
                    foreach ($domains as $domain => $pack) {
                        $availability[$domain] = $this->ModuleManager->moduleRpc(
                            $pack->package->module_id,
                            'checkAvailability',
                            [$domain],
                            $pack->package->module_row
                        );
                    }

                    if (($errors = $this->ModuleManager->errors())) {
                        $this->Input->setErrors($errors);
                    }
                }

                $this->view->set('availability', $availability);
            }

            $this->view->set('periods', $this->getPricingPeriods());
        }

        // Set the package pricings as transfers, if apply
        foreach ($domains as $domain => $pack) {
            foreach ($pack->package->pricing as $key => $pricing) {
                if (isset($post['transfer'])) {
                    $domains[$domain]->package->pricing[$key]->transfer = $post['transfer'];
                }
            }
        }

        // If the "Domain Manager" plugin is installed, then use it to fetch spotlight TLDs
        $domains_spotlight_tlds = $this->Companies->getSetting(
            Configure::get('Blesta.company_id'),
            'domains_spotlight_tlds'
        );
        $domains_package_group = $this->Companies->getSetting(
            Configure::get('Blesta.company_id'),
            'domains_package_group'
        );
        $meta = $this->formatMeta($this->order_form->meta);
        $spotlight_tlds = json_decode($domains_spotlight_tlds->value ?? '', true);

        // If there are not spotlights, use the first 4 TLDs
        if (empty($spotlight_tlds)) {
            $spotlight_tlds = array_slice($tlds, 0, 4);
        }

        if (!empty($spotlight_tlds)
            && $domains_package_group
            && $meta['domain_group'] == $domains_package_group->value
        ) {
            $this->view->set('spotlight_tlds', $spotlight_tlds);
        }

        // Fetch the TLDs pricing table from cache, if exists
        if ($get['getAllTlds'] ?? false) { //$this->isAjax()
            $html_cache = Cache::fetchCache(
                'tlds_pricing_table_' . $currency . '_' . Configure::get('Blesta.language') . '_' . $this->order_form->id,
                Configure::get('Blesta.company_id') . DS . 'plugins' . DS . 'order' . DS
            );

            if ($html_cache) {
                echo json_encode(['html' => $html_cache]);
                exit();
            }
        }

        // Fetch the pricing of all TLDs
        Loader::loadComponents($this, ['Record']);

        $tlds_pricing = $tlds;
        $tld_pricing_list = [];
        foreach ($tlds_pricing as $tld => $packages) {
            $package_id = $packages[0]->id ?? null;

            if ((is_null($package_id)
                    || !(array_key_exists($tld, $spotlight_tlds ?? []) || ($get['getAllTlds'] ?? false)))
                && $domains_package_group
                && $meta['domain_group'] == $domains_package_group->value
            ) {
                continue;
            }

            $package = $this->updatePackagePricing($packages[0], $currency);

            $tld_pricing_list[$tld] = $package->pricing;
        }

        // Set order form meta
        if (!empty($this->order_form)) {
            $this->order_form->meta = $this->Form->collapseObjectArray(
                $this->order_form->meta,
                'value',
                'key'
            );
        }

        // Mark the domains that are eligible for free
        $free_domains = [];
        if (($this->order_form->meta['free_domain'] ?? '0') == '1') {
            foreach ($domains as $domain => &$package) {
                $tld = strstr($domain, '.');
                $package->free_eligible = false;

                if (in_array($tld, $this->order_form->meta['tlds'] ?? [])) {
                    $package->free_eligible = true;
                    $free_domains[] = $domain;
                }

                if (empty($package->package->pricing)) {
                    unset($domains[$domain]);
                }
            }
        }
        
        // Make a list of package group names that are eligible for free domain bundling
        $bundle_package_group_names = [];
        foreach ($this->order_form->meta['package_groups'] ?? [] as $bundle_group_id) {
            $package_group = $this->PackageGroups->get($bundle_group_id);
            $bundle_package_group_names[] = $package_group->name;
        }
        
        $this->view->base_uri = $get['base_uri'];
        $this->view->set('order_form', $this->order_form);
        $this->view->set('domains', $domains);
        $this->view->set('tlds', $tlds);
        $this->view->set('tlds_pricing', $tld_pricing_list);
        $this->view->set('free_domains', $free_domains);
        $this->view->set('bundle_package_group_names', implode(', ', $bundle_package_group_names));
        $this->view->set('vars', (object)$post);
        $this->view->set('currency', $currency);

        if ($get['getAllTlds'] ?? false) {
            $html = $this->view->fetch('tld_pricing_rows');

            // Save TLDs pricing table in cache
            if (Configure::get('Caching.on') && is_writable(CACHEDIR)) {
                try {
                    if (!file_exists(CACHEDIR . Configure::get('Blesta.company_id') . DS . 'plugins')) {
                        mkdir(CACHEDIR . Configure::get('Blesta.company_id') . DS . 'plugins');
                    }

                    Cache::writeCache(
                        'tlds_pricing_table_' . $currency . '_' . Configure::get('Blesta.language') . '_' . $this->order_form->id,
                        $html,
                        strtotime(Configure::get('Blesta.cache_length')) - time(),
                        Configure::get('Blesta.company_id') . DS . 'plugins' . DS . 'order' . DS
                    );
                } catch (Exception $e) {
                    // Write to cache failed, so disable caching
                    Configure::set('Caching.on', false);
                }
            }

            echo json_encode(['html' => $html]);
            exit();
        } else {
            return $this->view->fetch('lookup');
        }
    }

    /**
     * Notifies the order type that the given action is complete, and allows
     * the other type to modify the URI the user is redirected to
     *
     * @param string $action The controller.action completed
     * @param array $params An array of optional key/value pairs specific to the given action
     * @return string The URI to redirec to, null to redirect to the default URI
     */
    public function redirectRequest($action, array $params = null)
    {
        switch ($action) {
            case 'config.index':
                $meta = $this->formatMeta($this->order_form->meta);
                $item = $this->cart->getItem($params['item_index']);

                if ($item && $item['group_id'] == $meta['domain_group']) {
                    return $this->base_uri . 'plugin/order/main/index/' . $this->order_form->label . '/?skip=true';
                }
                break;
        }
        return null;
    }

    /**
     * Returns all package groups that are valid for this order form
     *
     * @return array A numerically indexed array of package group IDs
     */
    public function getGroupIds()
    {
        $group_ids = parent::getGroupIds();

        $meta = $this->formatMeta($this->order_form->meta);
        $group_ids[] = $meta['domain_group'];

        return $group_ids;
    }

    /**
     * Handle the callback for the prequeueItem event
     *
     * @param EventInterface $event The event triggered when an item is prequeued for the cart
     */
    public function prequeueItem(EventInterface $event)
    {
        $params = $event->getParams();
        if (isset($params['item'])) {
            $item = $params['item'];
            $item['domain'] = $this->cart->getData('domain');

            $event->setReturnValue($item);
        }
    }

    /**
     * Handle the callback for theaddItem event
     *
     * @param EventInterface $event The event triggered when an item is added to the cart
     */
    public function addItem(EventInterface $event)
    {
        $params = $event->getParams();
        $meta = $this->formatMeta($this->order_form->meta);

        if ($params['item'] && $params['item']['group_id'] == $meta['domain_group']
            && isset($params['item']['domain'])) {
            $this->cart->setData('domain', $params['item']['domain']);
        }
    }

    /**
     * Set all pricing periods
     */
    private function getPricingPeriods()
    {
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }

        // Set language for periods
        $periods = $this->Packages->getPricingPeriods();
        foreach ($this->Packages->getPricingPeriods(true) as $period => $lang) {
            $periods[$period . '_plural'] = $lang;
        }

        return $periods;
    }

    /**
     * Select the appropriate package to use for the given domain, then redirect to configure the package
     *
     * @param string $domain The domain
     * @param array An array key/value pairs where each key is a TLD and each value
     *  is an array containing the package and group used for that TLD
     * @return array An array containing the package group and package used for this, null if the TLD does not exist
     */
    private function domainPackageGroup($domain, array $tlds)
    {
        foreach ($tlds as $tld => $pack) {
            if ($this->getTld($domain) == $tld) {
                return $pack;
            }
        }

        return null;
    }

    /**
     * Fetches all TLDs support by the order form
     *
     * @return array An array key/value pairs where each key is a TLD and each value is
     *  an array containing the package and group used for that TLD
     */
    private function getTlds()
    {
        if (!isset($this->Packages)) {
            Loader::loadModels($this, ['Packages']);
        }
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Session)) {
            Loader::loadComponents($this, ['Session']);
        }
        if (!isset($this->Form)) {
            Loader::loadComponents($this, ['Form']);
        }

        $tlds = [];

        $meta = $this->formatMeta($this->order_form->meta);
        $group = new stdClass();
        $group->order_form_id = $this->order_form->id;
        $group->package_group_id = $meta['domain_group'];

        // Fetch all packages for this group
        $packages[$group->package_group_id] = $this->Packages->getAllPackagesByGroup(
            $group->package_group_id,
            null,
            ['hidden' => true]
        );

        // Get tlds from restricted packages
        foreach (
            $this->Clients->getRestrictedPackages($this->Session->read('blesta_client_id')) as $client_package
        ) {
            $package = $this->Packages->get($client_package->package_id);
            if ($package) {
                foreach ($package->groups as $package_group) {
                    if ($package_group->id == $group->package_group_id && isset($package->meta->tlds)) {
                        foreach ($package->meta->tlds as $tld) {
                            if (isset($tlds[$tld])) {
                                continue;
                            }

                            $tlds[$tld] = [$package, $group];
                        }

                        break;
                    }
                }
            }
        }

        // Get tlds from regular packages
        foreach ($packages[$group->package_group_id] as $package) {
            if ($package && $package->status == 'active' && isset($package->meta->tlds)) {
                foreach ($package->meta->tlds as $tld) {
                    if (isset($tlds[$tld])) {
                        continue;
                    }

                    $tlds[$tld] = [$package, $group];
                }
            }
        }

        return $tlds;
    }

    /**
     * Returns the SLD for the given domain (ignoring www. as a subdomain)
     *
     * @param string $domain The domain to find the SLD of
     * @return string The SLD of $domain
     */
    private function getSld($domain)
    {
        $domain = preg_replace('/^www\./i', '', $domain);
        preg_match("/^(.*?)\.(.*)/i", $domain, $matches);

        return isset($matches[1]) ? $matches[1] : $domain;
    }

    /**
     * Returns the TLD for the given domain (ignoring www. as a subdomain)
     *
     * @param string $domain The domain to find the TLD of
     * @return string The TLD of $domain
     */
    private function getTld($domain)
    {
        $sld = $this->getSld($domain);
        preg_match("/" . $sld . "(.*)/i", $domain, $matches);

        return isset($matches[1]) ? $matches[1] : $domain;
    }

    /**
     * Format meta into key/value array
     *
     * @param array An array of stdClass object representing meta fields
     * @return array An array of key/value pairs
     */
    private function formatMeta($meta)
    {
        $result = [];
        foreach ($meta as $field) {
            $result[$field->key] = $field->value;
        }
        return $result;
    }
}
