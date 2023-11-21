<?php
/**
 * Support Manager Admin Main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.support_manager
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AdminMain extends SupportManagerController
{
    /**
     * Redirect to the AdminTickets controller
     */
    public function index()
    {
        $this->redirect($this->base_uri . 'plugin/support_manager/admin_tickets/');
    }
}
