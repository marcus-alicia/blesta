<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Clients event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Clients implements Observable
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
                if (isset($params['old_client']->user_id)) {
                    $this->clientsDelete($params['old_client']->user_id);
                }
                break;
        }
    }

    /**
     * Performs the Clients.delete action
     *
     * @param int $user_id The ID of the user to delete
     */
    private function clientsDelete($user_id)
    {
        $Loader = $this->getFromContainer('loader');
        $Loader->loadModels($this, ['Users']);

        // Delete the client's user
        $this->Users->delete($user_id);
    }
}
