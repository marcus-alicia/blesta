<?php
/**
 * Fetch Products, TLDs, Product Pricing, and TLD Pricing
 *
 */
class WhmcsProducts
{
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Fetches a PDOStatement for all products
     *
     * @return PDOStatement
     */
    public function get()
    {
        return $this->remote->select()->from('tblproducts')->getStatement();
    }

    /**
     * Fetches a PDOStatement for all TLDs
     *
     * @return PDOStatement
     */
    public function getTlds()
    {
        return $this->remote->select()->from('tbldomainpricing')->getStatement();
    }

    /**
     * Get pricing for the given type
     *
     * @param int $relid The ID of the product/configoption/etc.
     * @return array A numerically indexed array of pricing each containing:
     * 	- term
     * 	- period
     * 	- price
     * 	- setupfee
     * 	- currency
     */
    public function getPricing($relid, $type = 'product')
    {
        $fields = ['tblpricing.*', 'tblcurrencies.code' => 'currency_code'];
        $pricing = $this->remote->select($fields)->from('tblpricing')->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblpricing.currency', false)->
            where('tblpricing.type', '=', $type)->
            where('tblpricing.relid', '=', $relid)->fetchAll();

        $onetime = false;
        if ($type == 'product') {
            $onetime = (boolean)$this->remote->select()->
                from('tblproducts')->where('id', '=', $relid)->
                where('paytype', '=', 'onetime')->fetch();
        }

        $terms = [
            'monthly' => [1, 'month'],
            'quarterly' => [3, 'month'],
            'semiannually' => [6, 'month'],
            'annually' => [1, 'year'],
            'biennially' => [2, 'year'],
            'triennially' => [3, 'year']
        ];

        if ($onetime) {
            $terms = [
                'monthly' => [0, 'onetime']
            ];
        }

        $prices = [];
        foreach ($pricing as $price) {
            foreach ($terms as $price_point => $term) {
                if ((string)$price->{$price_point} == '-1.00') {
                    continue;
                }

                $prices[] = [
                    'term' => $term[0],
                    'period' => $term[1],
                    'price' => $price->{$price_point},
                    'setup_fee' => $price->{substr($price_point, 0, 1) . 'setupfee'},
                    'currency' => $price->currency_code
                ];
            }

            if ($type == 'configoptions' && (string)$price->monthly != '-1.00') {
                $prices[] = [
                    'term' => 0,
                    'period' => 'onetime',
                    'price' => $price->monthly,
                    'setup_fee' => 'msetupfee',
                    'currency' => $price->currency_code
                ];
            }
        }

        return $prices;//$this->mergePrices($prices, $this->getPricingOverrides($product_id));
    }

    /**
     * Fetches all pricing overrides (not necessarily unique from package pricing, but overriden nonetheless)
     *
     * @param int $product_id The product ID
     * @return array A numerically indexed array of pricing each containing:
     * 	- term
     * 	- period
     * 	- price
     * 	- currency
     */
    private function getPricingOverrides($product_id)
    {
        $fields = ['tblhosting.billingcycle', 'tblhosting.amount',
            'tblcurrencies.code' => 'currency_code'];
        $pricing = $this->remote->select($fields)->from('tblhosting')->
            innerJoin('tblproducts', 'tblproducts.id', '=', 'tblhosting.packageid', false)->
            innerJoin('tblclients', 'tblclients.id', '=', 'tblhosting.userid', false)->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tblhosting.packageid', '=', $product_id)->
            group(['tblhosting.billingcycle', 'tblhosting.amount', 'tblcurrencies.code'])->
            fetchAll();

        $terms = [
            'free account' => [0, 'onetime'],
            'one time' => [0, 'onetime'],
            'monthly' => [1, 'month'],
            'quarterly' => [3, 'month'],
            'semi-annually' => [6, 'month'],
            'annually' => [1, 'year'],
            'biennially' => [2, 'year'],
            'triennially' => [3, 'year']
        ];

        $prices = [];
        foreach ($pricing as $price) {
            $price->billingcycle = strtolower($price->billingcycle);
            if (!isset($terms[$price->billingcycle])) {
                continue;
            }

            $prices[] = [
                'term' => $terms[$price->billingcycle][0],
                'period' => $terms[$price->billingcycle][1],
                'price' => $price->amount,
                'setup_fee' => 0,
                'currency' => $price->currency_code
            ];
        }
        return $prices;
    }

    /**
     * Get pricing for the given domain TLD
     *
     * @param int $tld The domain TLD
     * @param string $type The type of pricing to fetch ('domainregister', 'domaintransfer', 'domainrenew', 'domainaddons')
     * @return array A numerically indexed array of pricing each containing:
     * 	- term
     * 	- period
     * 	- price
     * 	- setupfee
     * 	- currency
     */
    public function getTldPricing($tld, $type = 'domainregister')
    {
        $fields = ['tblpricing.*', 'tblcurrencies.code' => 'currency_code'];
        $pricing = $this->remote->select($fields)->from('tblpricing')->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblpricing.currency', false)->
            innerJoin('tbldomainpricing', 'tbldomainpricing.id', '=', 'tblpricing.relid', false)->
            where('tblpricing.type', '=', $type)->
            where('tbldomainpricing.extension', '=', $tld)->
            fetchAll();

        $terms = [
            'msetupfee' => [1, 'year'],
            'qsetupfee' => [2, 'year'],
            'ssetupfee' => [3, 'year'],
            'asetupfee' => [4, 'year'],
            'bsetupfee' => [5, 'year'],
            'monthly' => [6, 'year'],
            'quarterly' => [7, 'year'],
            'semiannually' => [8, 'year'],
            'annually' => [9, 'year'],
            'biennially' => [10, 'year']
        ];

        $prices = [];
        foreach ($pricing as $price) {
            foreach ($terms as $price_point => $term) {
                if ((string)$price->{$price_point} == ($type == 'domainregister' ? '0.00' : '-1.00')) {
                    continue;
                }

                $prices[] = [
                    'term' => $term[0],
                    'period' => $term[1],
                    'price' => $price->{$price_point},
                    'currency' => $price->currency_code
                ];
            }
        }

        return $prices;//$this->mergePrices($prices, $this->getTldPricingOverrides($tld));
    }

    /**
     * Fetches all pricing overrides (not necessarily unique from package pricing, but overriden nonetheless)
     *
     * @param int $product_id The product ID
     * @return array A numerically indexed array of pricing each containing:
     * 	- term
     * 	- period
     * 	- price
     * 	- setupfee
     * 	- currency
     */
    private function getTldPricingOverrides($tld)
    {
        $fields = ['tbldomains.registrationperiod', 'tbldomains.recurringamount',
            'tblcurrencies.code' => 'currency_code'];
        $pricing = $this->remote->select($fields)->from('tbldomains')->
            innerJoin('tbldomainpricing', 'tbldomainpricing.autoreg', '=', 'tbldomains.registrar', false)->
            innerJoin('tblclients', 'tblclients.id', '=', 'tbldomains.userid', false)->
            innerJoin('tblcurrencies', 'tblcurrencies.id', '=', 'tblclients.currency', false)->
            where('tbldomainpricing.extension', '=', $tld)->
            group(['tbldomains.registrationperiod', 'tbldomains.recurringamount', 'tblcurrencies.code'])->
            fetchAll();

        $prices = [];
        foreach ($pricing as $price) {
            $prices[] = [
                'term' => $price->registrationperiod,
                'period' => 'year',
                'price' => $price->recurringamount,
                'setup_fee' => 0,
                'currency' => $price->currency_code
            ];
        }
        return $prices;
    }

    /**
     * Merges prices between two pricing arrays based on term, period, and currency
     *
     * @param array $prices1
     * @param array $prices2
     * @return array An array of merged pricing
     */
    private function mergePrices($prices1, $prices2)
    {
        foreach ($prices2 as $price) {
            $add = true;
            foreach ($prices1 as $a_price) {
                if ($a_price['term'] == $price['term'] &&
                    $a_price['period'] == $price['period'] &&
                    (string)$a_price['price'] == (string)$price['price'] &&
                    $a_price['currency'] == $price['currency']) {
                    $add = false;
                    break;
                }
            }

            if ($add) {
                $prices1[] = $price;
            }
        }

        return $prices1;
    }

    /**
     * Get Generic module rows
     *
     * @return PDOStatement Each row representing a module row
     */
    public function getServers()
    {
        return $this->remote->select()->from('tblservers')->getStatement();
    }

    /**
     * Get All Registrars
     *
     * @return array An array of all installed registrars
     */
    public function getReigstrars()
    {
        $data = $this->remote->select(['registrar'])->
            from('tblregistrars')->group(['registrar'])->fetchAll();

        $registrars = [];
        foreach ($data as $value) {
            $registrars[] = $value->registrar;
        }
        return $registrars;
    }

    /**
     * Returns a key/value pair array of field names and values for the given registrar
     *
     * @param string $registrar The registrar to fetch all key/value pairs for
     * @return array An array of key/value pairs for the registrar
     */
    public function getRegistrarFields($registrar)
    {
        $fields = [];
        $data = $this->remote->select()->from('tblregistrars')->
            where('registrar', '=', $registrar)->fetchAll();

        foreach ($data as $field) {
            $fields[strtolower($field->setting)] = $field->value;
        }
        return $fields;
    }

    /**
     * Fetches all config options for a specific group ID
     *
     * @param int $group_id The ID of the group to fetch options for
     * @return array An array of stdClass objects, each representing a config option with its values
     */
    public function getConfigOptions($group_id)
    {
        $options = $this->remote->select()->from('tblproductconfigoptions')->
            where('gid', '=', $group_id)->fetchAll();
        foreach ($options as $option) {
            $option->values = $this->remote->select()->from('tblproductconfigoptionssub')->
                order(['sortorder' => 'ASC'])->
                where('configid', '=', $option->id)->fetchAll();
        }

        return $options;
    }

    /**
     * Fetches all config options groups
     *
     * @return array An array of package option groups, with associated packages
     */
    public function getConfigOptionGroups()
    {
        $groups = $this->remote->select()->from('tblproductconfiggroups')->fetchAll();
        foreach ($groups as $group) {
            $group->packages = [];

            $links = $this->getConfigOptionGroupLinks($group->id);
            foreach ($links as $link) {
                $group->packages[] = $link->pid;
            }
        }
        return $groups;
    }

    /**
     * Returns config option types
     *
     * @return array An array of optiontype values and their representation
     */
    public function getConfigOptionTypes()
    {
        return [
            1 => 'select',
            2 => 'radio',
            3 => 'checkbox',
            4 => 'quantity'
        ];
    }

    /**
     * Fetches all group to package associations
     *
     * @param int $group_id The ID of the group to fetch associations on
     * @return array An array of objects, each representing a group and package assignment
     */
    private function getConfigOptionGroupLinks($group_id)
    {
        return $this->remote->select()->from('tblproductconfiglinks')->
            where('gid', '=', $group_id)->fetchAll();
    }
}
