<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The User Log event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class LogUsers implements Observable
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
            case 'Users.delete':
                if (isset($params['user_id'])) {
                    $this->usersDelete($params['user_id']);
                }
                break;
        }
    }

    /**
     * Performs the Users.delete action
     *
     * @param int $user_id The ID of the user to delete
     */
    private function usersDelete($user_id)
    {
        $Loader = $this->getFromContainer('loader');
        $Loader->loadModels($this, ['Logs']);

        // Delete all user logs
        $this->Logs->deleteUser($user_id);
    }
}
