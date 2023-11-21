<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Transactions event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Transactions implements Observable
{
    // Include traits
    use Container;

    /**
     * {@inheritdoc}
     */
    public function update(EventInterface $event)
    {
        $params = $event->getParams();

        switch ($event->getName()) {
            case 'Clients.delete':
                $this->clientsDelete($params['client_id']);
                break;
        }
    }

    /**
     * Performs the Clients.delete action
     *
     * @param int $client_id The ID of the client to delete
     */
    private function clientsDelete($client_id)
    {
        $Loader = $this->getFromContainer('loader');
        $Loader->loadModels($this, ['Logs', 'Transactions']);

        // Delete all transactions for this client
        while (($transactions = $this->Transactions->getSimpleList($client_id))) {
            foreach ($transactions as $transaction) {
                $this->Transactions->delete($transaction->id);
                #
                # TODO: A LogTransactions event handler should exist that listens to a Transactions.delete event
                # to perform this action
                #
                $this->Logs->deleteTransaction($transaction->id);
            }
        }
    }
}
