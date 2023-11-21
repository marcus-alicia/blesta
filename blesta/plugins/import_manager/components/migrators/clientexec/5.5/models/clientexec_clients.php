<?php
/**
 * Generic Clientexec Clients Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecClients
{
    /**
     * ClientexecClients constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all clients.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('users')->where('groupid', '=', 1)->fetchAll();
    }

    /**
     * Get all clients notes.
     *
     * @return mixed The result of the sql transaction
     */
    public function getNotes()
    {
        return $this->remote->select()->from('clients_notes')->fetchAll();
    }
}
