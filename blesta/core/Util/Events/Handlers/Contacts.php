<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Contacts event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Contacts implements Observable
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
            case 'Contacts.delete':
                if (isset($params['old_contact']->user_id)) {
                    $this->contactsDelete($params['old_contact']->user_id);
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
        $Loader->loadModels($this, ['Contacts']);

        $contacts = $this->Contacts->getAll($client_id);

        // Delete all of the client's contacts
        foreach ($contacts as $contact) {
            $this->Contacts->delete($contact->id);
        }
    }

    /**
     * Performs the Contacts.delete action
     *
     * @param int $user_id The ID of the user to delete
     */
    private function contactsDelete($user_id)
    {
        $Loader = $this->getFromContainer('loader');
        $Loader->loadModels($this, ['Users']);

        // Delete the contact's user
        $this->Users->delete($user_id);
    }
}
