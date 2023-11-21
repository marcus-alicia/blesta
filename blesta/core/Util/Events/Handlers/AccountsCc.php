<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Accounts Credit Card event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AccountsCc implements Observable
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
            case 'Contacts.delete':
                if (isset($params['contact_id'])) {
                    $this->contactsDelete($params['contact_id']);
                }
                break;
        }
    }

    /**
     * Performs the Contacts.delete action
     *
     * @param int $contact_id The ID of the contact to delete
     */
    private function contactsDelete($contact_id)
    {
        $Loader = $this->getFromContainer('loader');
        $Loader->loadModels($this, ['Accounts']);

        // Delete all of the contact's CC Accounts
        $ccAccounts = $this->Accounts->getAllCc($contact_id);

        foreach ($ccAccounts as $account) {
            $this->Accounts->deleteCc($account->id, false);
        }
    }
}
