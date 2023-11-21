<?php
/**
 * Generic Clientexec Currencies Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecCurrencies
{
    /**
     * ClientexecCurrencies constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all enabled currencies.
     *
     * @return mixed The result of the sql transaction
     */
    public function getEnabled()
    {
        return $this->remote->select()->from('currency')->where('enabled', '=', '1')->fetchAll();
    }

    /**
     * Get the default currency.
     *
     * @return mixed The result of the sql transaction
     */
    public function getDefault()
    {
        $currency = $this->remote->select()->from('setting')->where('name', '=', 'Default Currency')->fetch();

        return !empty($currency->value) ? $currency->value : 'USD';
    }
}
