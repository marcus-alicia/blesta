<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Client Values event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class ClientValues implements Observable
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
                if (isset($params['client_id'])) {
                    $this->clientsDelete($params['client_id']);
                }
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
        $Loader->loadModels($this, ['Clients']);

        // Delete the client's custom field values
        $this->Clients->deleteCustomFieldValues($client_id);
    }
}
