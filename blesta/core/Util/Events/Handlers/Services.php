<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Services event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Services implements Observable
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
        $Loader->loadModels($this, ['Logs', 'Services', 'ServiceChanges']);

        // Delete all services for this client
        while (($services = $this->Services->getSimpleList($client_id))) {
            foreach ($services as $service) {
                $this->Services->delete($service->id, false);
                #
                # TODO: A LogServices event handler should exist that listens to a Services.delete event to perform
                # this action
                #
                $this->Logs->deleteService($service->id);
                #
                # TODO: A ServiceChanges event handler should exist that listens to a Services.delete event to perform
                # this action
                #
                $this->ServiceChanges->deletebyService($service->id);
            }
        }
    }
}
