<?php
/**
 * Shared Login main controller
 *
 * @package blesta
 * @subpackage blesta.plugins.shared_login
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Main extends AppController
{
    /**
     * Handle all shared login requests
     */
    public function index()
    {
        $hash = isset($this->get['h']) ? $this->get['h'] : null;
        $username = isset($this->get['u']) ? $this->get['u'] : null;
        $time = isset($this->get['t']) ? $this->get['t'] : null;
        $uri = isset($this->get['r']) ? $this->get['r'] : null;

        $key = $this->Companies->getSetting($this->company_id, 'shared_login.key');
        if (!$key) {
            return false;
        }

        if ($hash == $this->Companies->systemHash($time . $username . $uri, $key->value, 'sha256')
            && $time >= strtotime('-30 min')) {
            return $this->processSharedLogin($username, $uri);
        }
        return false;
    }

    /**
     * Handle logging the user in
     *
     * @param string $username The user's username
     * @param string $uri The URI to redirec to
     */
    private function processSharedLogin($username, $uri)
    {
        $this->uses(['Clients', 'Users', 'Logs']);
        $user = $this->Users->getByUsername($username);

        if ($user && $user->two_factor_mode == 'none') {
            $client = $this->Clients->getByUserId($user->id);
            if ($client) {
                $this->Session->write('blesta_id', $user->id);
                $this->Session->write('blesta_company_id', $this->company_id);
                $this->Session->write('blesta_client_id', $client->id);
                $requestor = $this->getFromContainer('requestor');

                // Log this user
                $log = [
                    'user_id' => $user->id,
                    'ip_address' => $requestor->ip_address,
                    'company_id' => $this->company_id,
                    'result' => 'success'
                ];
                $this->Logs->addUser($log);
                return $this->returnResponse($uri);
            }
        }
        return false;
    }

    /**
     * Redirect the user to the given URI, if none given redirect the user
     * to the client portal (except in cases of AJAX requests)
     *
     * @param string $uri The URI to redirect to
     */
    private function returnResponse($uri)
    {
        if ($uri == null) {
            if ($this->isAjax()) {
                $this->outputAsJson(['success' => true]);
                return false;
            }
            $this->redirect($this->client_uri);
        }
        $this->redirect($uri);
    }
}
