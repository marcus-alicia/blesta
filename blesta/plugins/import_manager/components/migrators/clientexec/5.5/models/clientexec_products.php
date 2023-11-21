<?php
/**
 * Generic Clientexec Products Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecProducts
{
    /**
     * ClientexecProducts constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all products.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('package')->fetchAll();
    }

    /**
     * Get Generic module rows.
     *
     * @return mixed The result of the sql transaction
     */
    public function getServers()
    {
        return $this->remote->select()->from('server')->fetchAll();
    }

    /**
     * Get an specific server.
     *
     * @param mixed $server_id
     * @return mixed The result of the sql transaction
     */
    public function getServer($server_id)
    {
        return $this->remote->select()->from('server')->where('id', '=', $server_id)->fetch();
    }

    /**
     * Get the fields of an specific server.
     *
     * @param mixed $server_id
     * @return mixed The result of the sql transaction
     */
    public function getServerFields($server_id)
    {
        return $this->remote->select()->from('serverplugin_options')->where('serverid', '=', $server_id)->fetchAll();
    }

    /**
     * Get the name servers of an specific server.
     *
     * @param mixed $server_id
     * @return mixed The result of the sql transaction
     */
    public function getServerNameservers($server_id)
    {
        return $this->remote->select()->from('nameserver')->where('serverid', '=', $server_id)->fetchAll();
    }

    /**
     * Get an specific package.
     *
     * @param mixed $package_id
     * @return mixed The result of the sql transaction
     */
    public function getProduct($package_id)
    {
        return $this->remote->select()->from('package')->where('id', '=', $package_id)->fetch();
    }

    /**
     * Get the pricing of an specific package.
     *
     * @param mixed $package_id
     * @return mixed The result of the sql transaction
     */
    public function getProductPricing($package_id)
    {
        $package = $this->remote->select()->from('package')->where('id', '=', $package_id)->fetch();
        $pricing = unserialize($package->pricing);
        $currency = $this->remote->select()->from('setting')->where('name', '=', 'Default Currency')->fetch();
        $currency = !empty($currency->value) ? $currency->value : 'USD';

        $pricing_terms = [];

        if (!isset($pricing['pricedata'])) {
            // Monthly term
            if (isset($pricing['price1']) && $pricing['price1'] > 0) {
                $pricing_terms[] = [
                    'term' => 1,
                    'period' => 'month',
                    'price' => number_format($pricing['price1'], 4, '.', ''),
                    'setup_fee' => number_format($pricing['price1_setup'], 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Quarterly term
            if (isset($pricing['price3']) && $pricing['price3'] > 0) {
                $pricing_terms[] = [
                    'term' => 3,
                    'period' => 'month',
                    'price' => number_format($pricing['price3'], 4, '.', ''),
                    'setup_fee' => number_format($pricing['price3_setup'], 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Semi-annual term
            if (isset($pricing['price6']) && $pricing['price6'] > 0) {
                $pricing_terms[] = [
                    'term' => 6,
                    'period' => 'month',
                    'price' => number_format($pricing['price6'], 4, '.', ''),
                    'setup_fee' => number_format($pricing['price6_setup'], 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Annual term
            if (isset($pricing['price12']) && $pricing['price12'] > 0) {
                $pricing_terms[] = [
                    'term' => 1,
                    'period' => 'year',
                    'price' => number_format($pricing['price12'], 4, '.', ''),
                    'setup_fee' => number_format($pricing['price12_setup'], 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Biannually term
            if (isset($pricing['price24']) && $pricing['price24'] > 0) {
                $pricing_terms[] = [
                    'term' => 2,
                    'period' => 'year',
                    'price' => number_format($pricing['price24'], 4, '.', ''),
                    'setup_fee' => number_format($pricing['price24_setup'], 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // One-time term
            if (isset($pricing['onetime']) && $pricing['onetime'] > 0) {
                $pricing_terms[] = [
                    'term' => 0,
                    'period' => 'onetime',
                    'price' => number_format($pricing['onetime'], 4, '.', ''),
                    'currency' => $currency
                ];
            }
        } else {
            foreach ($pricing['pricedata'][0] as $price) {
                if (is_array($price)) {
                    $pricing_terms[] = [
                        'term' => $price['period'],
                        'period' => $price['period'] == '0' ? 'onetime' : 'year',
                        'price' => number_format($price['price'], 4, '.', ''),
                        'currency' => $currency
                    ];
                }
            }
        }

        // If pricing terms are empty, we assume that it's a free product
        if (empty($pricing_terms)) {
            $pricing_terms[] = [
                'term' => 1,
                'period' => 'month',
                'price' => '0.0000',
                'setup_fee' => '0.0000',
                'currency' => $currency
            ];
        }

        return $pricing_terms;
    }

    /**
     * Get the server of a specific package.
     *
     * @param mixed $package_id
     * @return mixed The result of the sql transaction
     */
    public function getProductServer($package_id)
    {
        return $this->remote->select('server.*')
            ->from('package_server')
            ->innerJoin('server', 'server.id', '=', 'package_server.server_id', false)
            ->where('package_server.package_id', '=', $package_id)
            ->fetch();
    }

    /**
     * Get the fields of an specific package.
     *
     * @param mixed $package_id
     * @return mixed The result of the sql transaction
     */
    public function getProductFields($package_id)
    {
        $product_fields = $this->remote->select()->from('package_variable')->where('packageid', '=', $package_id)->fetchAll();

        foreach ($product_fields as $key => $value) {
            $product_fields[$key]->varname = str_replace(' ', '_', strtolower($value->varname));
        }

        return $product_fields;
    }

    /**
     * Get all registrars.
     *
     * @return array An array of all installed registrars
     */
    public function getReigstrars()
    {
        return [
            'enom',
            'internetbs',
            'namesilo',
            'netearthone',
            'netim',
            'onlinenic',
            'opensrs',
            'planetdomain',
            'realtimeregister',
            'resellbiz',
            'resellerclub',
            'resellone'
        ];
    }

    /**
     * Returns a key/value pair array of field names and values for the given registrar.
     *
     * @param string $registrar The registrar to fetch all key/value pairs for
     * @return array An array of key/value pairs for the registrar
     */
    public function getRegistrarFields($registrar)
    {
        return $this->remote->select()->from('setting')->where('name', 'LIKE', 'plugin_' . $registrar . '_%')->fetchAll();
    }

    /**
     * Get all the products groups.
     *
     * @return mixed The result of the sql transaction
     */
    public function getGroups()
    {
        return $this->remote->select()->from('promotion')->fetchAll();
    }

    /**
     * Get all the products groups.
     *
     * @param mixed $group_id
     * @return mixed The result of the sql transaction
     */
    public function getGroup($group_id)
    {
        return $this->remote->select()->from('promotion')->where('id', '=', $group_id)->fetchAll();
    }

    /**
     * Get all the products addons.
     *
     * @return mixed The result of the sql transaction
     */
    public function getAddons()
    {
        return $this->remote->select()->from('packageaddon')->where('name', '!=', 'No name given')->fetchAll();
    }

    /**
     * Get all the packages from an specific addon.
     *
     * @param mixed $addon_id
     * @return mixed The result of the sql transaction
     */
    public function getAddonPackages($addon_id)
    {
        return $this->remote->select()->from('product_addon')->where('addon_id', '!=', $addon_id)->fetchAll();
    }

    /**
     * Get all the prices from an specific addon.
     *
     * @param mixed $addon_id
     * @return mixed The result of the sql transaction
     */
    public function getAddonPricing($addon_id)
    {
        $prices = $this->remote->select()->from('packageaddon_prices')->where('packageaddon_id', '=', $addon_id)->fetchAll();
        $currency = $this->remote->select()->from('setting')->where('name', '=', 'Default Currency')->fetch();
        $currency = !empty($currency->value) ? $currency->value : 'USD';
        $values = [];

        foreach ($prices as $price) {
            $pricing = [];

            // Month term
            if ($price->price1 > 0) {
                $pricing[] = [
                    'term' => 1,
                    'period' => 'month',
                    'price' => number_format($price->price1, 4, '.', ''),
                    'setup_fee' => number_format($price->price0, 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Quaterly term
            if ($price->price3 > 0) {
                $pricing[] = [
                    'term' => 3,
                    'period' => 'month',
                    'price' => number_format($price->price3, 4, '.', ''),
                    'setup_fee' => number_format($price->price0, 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Semi-annual term
            if ($price->price6 > 0) {
                $pricing[] = [
                    'term' => 6,
                    'period' => 'month',
                    'price' => number_format($price->price6, 4, '.', ''),
                    'setup_fee' => number_format($price->price0, 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Annual term
            if ($price->price12 > 0) {
                $pricing[] = [
                    'term' => 1,
                    'period' => 'year',
                    'price' => number_format($price->price12, 4, '.', ''),
                    'setup_fee' => number_format($price->price0, 4, '.', ''),
                    'currency' => $currency
                ];
            }

            // Biannually term
            if ($price->price24 > 0) {
                $pricing[] = [
                    'term' => 2,
                    'period' => 'year',
                    'price' => number_format($price->price24, 4, '.', ''),
                    'setup_fee' => number_format($price->price0, 4, '.', ''),
                    'currency' => $currency
                ];
            }

            $values[] = [
                'name' => $price->detail,
                'value' => str_replace(' ', '_', strtolower($price->detail)),
                'min' => null,
                'max' => null,
                'step' => null,
                'pricing' => $pricing
            ];
        }

        return $values;
    }

    /**
     * Get all the addons groups.
     *
     * @return mixed The result of the sql transaction
     */
    public function getAddonsGroups()
    {
        $groups = $this->remote->select()->from('productgroup_addon')->fetchAll();

        $addon_groups = [];
        foreach ($groups as $group) {
            $addon_groups[$group->productgroup_id] = $group->addon_id;
        }

        return $addon_groups;
    }
}
