<?php
/**
 * Generic Clientexec Services Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecServices
{
    /**
     * ClientexecServices constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Fetch all standard services.
     *
     * @return PDOStatement
     */
    public function get()
    {
        return $this->remote->select()->from('domains')->getStatement()->fetchAll();
    }

    /**
     * Fetch all the fields from an specific service.
     *
     * @param mixed $service_id
     * @return PDOStatement
     */
    public function getServiceFields($service_id)
    {
        $fields = $this->remote->select()->from('object_customField')->where('objectid', '=', $service_id)->getStatement()->fetchAll();

        foreach ($fields as $key => $value) {
            $custom_field = $this->remote->select()->from('customField')->where('id', '=', $value->customfieldid)->getStatement()->fetch();
            $fields[$key]->name = str_replace(' ', '_', strtolower($custom_field->name));
        }

        return $fields;
    }

    /**
     * Get the service pricing.
     *
     * @param mixed $service_id
     * @return PDOStatement
     */
    public function getServicePricing($service_id)
    {
        $service = $this->remote->select()->from('domains')->where('id', '=', $service_id)->getStatement()->fetch();
        $package = $this->remote->select()->from('package')->where('id', '=', $service->plan)->getStatement()->fetch();
        $recurring = $this->remote->select()->from('recurringfee')->where('appliestoid', '=', $service_id)->getStatement()->fetch();
        $currency = $this->remote->select()->from('setting')->where('name', '=', 'Default Currency')->getStatement()->fetch();
        $pricing = unserialize($package->pricing);

        return [
            'term' => $recurring->paymentterm >= 12 ? ($recurring->paymentterm / 12) : $recurring->paymentterm,
            'period' => $recurring->paymentterm >= 12 ? 'year' : 'month',
            'price' => number_format($pricing['price' . $recurring->paymentterm], 4, '.', ''),
            'setup_fee' => number_format($pricing['price' . $recurring->paymentterm . '_setup'], 4, '.', ''),
            'currency' => !empty($currency->value) ? $currency->value : 'USD'
        ];
    }

    /**
     * Get the next renew date of an specific service.
     *
     * @param mixed $service_id
     * @return PDOStatement
     */
    public function getServiceNextRenewDate($service_id)
    {
        $recurring = $this->remote->select()->from('recurringfee')->where('appliestoid', '=', $service_id)->getStatement()->fetch();

        return $recurring->nextbilldate;
    }

    /**
     * Get the status of an specific service.
     *
     * @param mixed $service_id
     * @return PDOStatement
     */
    public function getServiceStatus($service_id)
    {
        $service = $this->remote->select()->from('domains')->where('id', '=', $service_id)->getStatement()->fetch();

        $status = [
            0 => 'pending',
            1 => 'active',
            2 => 'suspended',
            3 => 'canceled',
            4 => 'canceled',
            5 => 'canceled'
        ];

        return $status[$service->status];
    }
}
