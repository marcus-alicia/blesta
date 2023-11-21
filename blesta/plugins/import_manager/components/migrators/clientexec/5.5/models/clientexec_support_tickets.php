<?php
/**
 * Generic Clientexec Support tickets Migrator.
 *
 * @package blesta
 * @subpackage blesta.plugins.import_manager.components.migrators.clientexec
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientexecSupportTickets
{
    /**
     * ClientexecSupportTickets constructor.
     *
     * @param Record $remote
     */
    public function __construct(Record $remote)
    {
        $this->remote = $remote;
    }

    /**
     * Get all tickets.
     *
     * @return mixed The result of the sql transaction
     */
    public function get()
    {
        return $this->remote->select()->from('troubleticket')->getStatement()->fetchAll();
    }

    /**
     * Get all the replies from an specific ticket.
     *
     * @param mixed $ticket_id
     * @return mixed The result of the sql transaction
     */
    public function getTicketReplies($ticket_id)
    {
        return $this->remote->select()->from('troubleticket_log')->where('troubleticketid', '=', $ticket_id)->getStatement()->fetchAll();
    }

    /**
     * Get all canned responses.
     *
     * @return mixed The result of the sql transaction
     */
    public function getCannedResponses()
    {
        return $this->remote->select()->from('canned_response')->getStatement()->fetchAll();
    }
}
