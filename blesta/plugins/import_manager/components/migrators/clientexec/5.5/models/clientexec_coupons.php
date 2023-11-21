<?php
/**
 * Generic Clientexec Coupons Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecCoupons
{
    /**
     * ClientexecCoupons constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all coupons.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('coupons')->getStatement()->fetchAll();
    }

    /**
     * Get all coupons packages.
     *
     * @return mixed The result of the sql transaction
     */
    public function getCouponsPackages()
    {
        return $this->remote->select()->from('coupons_packages')->getStatement()->fetchAll();
    }
}
