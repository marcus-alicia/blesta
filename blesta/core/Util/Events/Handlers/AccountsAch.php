<?php
namespace Blesta\Core\Util\Events\Handlers;

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\Events\Common\Observable;
use Blesta\Core\Util\Events\Common\EventInterface;

/**
 * The Accounts ACH event handler
 *
 * @package blesta
 * @subpackage blesta.core.Util.Events.Handlers
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class AccountsAch implements Observable
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

        // Delete all of the contact's ACH Accounts
        $achAccounts = $this->Accounts->getAllAch($contact_id);

        foreach ($achAccounts as $account) {
            $this->Accounts->deleteAch($account->id, false);
        }
    }
}
