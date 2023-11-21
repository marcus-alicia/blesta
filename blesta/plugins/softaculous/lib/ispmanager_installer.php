<?php
include_once dirname(__FILE__) . DS . 'softaculous_installer.php';

class IspmanagerInstaller extends SoftactulousInstaller
{

    /**
     * Validates informations and runs a softaculous installation script on ISPmanager
     *
     * @param stdClass $service An object representing the ISPmanager service to execute a script for
     * @param stdClass $meta The module row meta data for the service
     * @return boolean Whether the script succeeded
     */
    public function install(stdClass $service, stdClass $meta)
    {
        if (!isset($this->Clients)) {
            Loader::loadModels($this, ['Clients']);
        }
        if (!isset($this->Form)) {
            Loader::loadComponents($this, ['Form']);
        }

        // Get data for executing script
        $serviceFields = $this->Form->collapseObjectArray($service->fields, 'value', 'key');
        $configOptions = $this->Form->collapseObjectArray($service->options, 'value', 'option_name');
        $client = $this->Clients->get($service->client_id);

        // Authenticate in to ISPmanager account
        $this->setOptions(['request' => ['follow_location' => true, 'raw' => false]]);
        $loginData = [
            'authinfo' => $serviceFields['ispmanager_username'] . ':' . $serviceFields['ispmanager_password'],
            'func' => 'auth',
            'out' => 'json',
            'sok' => 'ok'
        ];
        $hostUrl = ($meta->use_ssl == 'true' ? 'https' : 'http') . '://' . $meta->host_name . ':1500/ispmgr';

        $loginResponse = $this->makeRequest(
            $loginData,
            $hostUrl,
            'GET'
        );

        if (!isset($loginResponse->doc->auth->{'$id'})) {
            $errorMessage = Language::_('SoftaculousPlugin.remote_error', true);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error(json_encode($loginResponse));
            return;
        }

        // Determine if the ISPManager installation is Lite or Business, by calling the 'node' function
        // which is only available to business installations.
        $session_id = $loginResponse->doc->auth->{'$id'};
        $nodeData = [
            'func' => 'node',
            'auth' => $session_id,
            'out' => 'json',
            'sok' => 'ok'
        ];
        $nodeResponse = $this->makeRequest(
            $nodeData,
            $hostUrl,
            'GET'
        );
        // Regardless of the installation type we should see an error.  It would be 'missed' for
        // lite and 'access' for business
        if (!isset($nodeResponse->doc->error)) {
            return;
        }

        // An error type of missed means that the function does not exist and thus this is a lite installation
        $is_lite = $nodeResponse->doc->error->{'$type'} == 'missed';

        $softaculous_url = ($meta->use_ssl == 'true' ? 'https' : 'http') . '://' . $meta->host_name . '/softaculous/';
        if ($is_lite) {
            // Make another authentication call in order to set authentication cookies
            $cookieData = ['func' => 'auth', 'auth' => $session_id, 'sok' => 'ok'];
            $this->makeRequest($cookieData, $hostUrl, 'GET');

            // Softaculous on ISPmanager requires a CSRF token for each call
            $configOptions = array_merge($configOptions, $this->getToken($softaculous_url));
        } else {
            // Authenticate in to Softaculous
            $softaculousData = [
                'auth' => $session_id,
                'func' => 'softaculous.redirect',
                'out' => 'json',
                'sok' => 'ok'
            ];

            $softaculousResponse = $this->makeRequest(
                $softaculousData,
                $hostUrl,
                'GET'
            );

            if (!isset($softaculousResponse->doc->ok->{'$'})) {
                $errorMessage = Language::_('SoftaculousPlugin.remote_error', true);
                $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
                $this->logger->error(json_encode($softaculousResponse));
                return;
            }

            // Get Softaculous login url
            $login = $softaculousResponse->doc->ok->{'$'};
            $query = explode('?', $login, 2);

            $login = isset($query[0]) ? $query[0] : null;
            $query = isset($query[1]) ? $query[1] : null;

            parse_str($query, $query);

            $urlData = [
                'func' => 'redirect',
                'auth' => $query['auth'],
                'authm' => $query['authm'],
                'lang' => $query['lang'],
                'redirect_uri' => $query['redirect_uri'],
                'sok' => 'ok'
            ];

            $urlResponse = $this->makeRequest(
                $urlData,
                $login,
                'GET'
            );

            // Softaculous on ISPmanager requires a CSRF token for each call
            $api = isset($urlResponse->location) ? $urlResponse->location : $login;
            $configOptions = array_merge($configOptions, $this->getToken($api));

            // Install script
            $softaculous_url = isset($urlResponse->location) ? $urlResponse->location : $login;
        }

        // Set installer options
        $this->setOptions(
            [
                'request' => [
                    'raw' => false,
                    'follow_location' => true,
                    'referer' => $softaculous_url . '?api=serialize&act=software'
                ]
            ]
        );


        return $this->installScript(
            (!empty($serviceFields['ispmanager_domain']) ? $serviceFields['ispmanager_domain'] : ''),
            $client->email,
            $softaculous_url,
            $configOptions
        );
    }

    /**
     * Get the CSRF token for the next request
     *
     * @param $url The Softaculous API url
     * @return array An array contaning the CSRF token and soft status key
     */
    private function getToken($url)
    {
        $stored_options = $this->options;
        // Set the options for the current request
        $this->setOptions(['request' => ['raw' => true]]);

        $params = [
            'act' => 'software',
            'soft' => 26
        ];
        $tokenResponse = $this->makeRequest($params, $url, 'GET');

        $csrf_token = explode('name="csrf_token" value="', $tokenResponse, 2);
        $csrf_token = explode('" />', (isset($csrf_token[1]) ? $csrf_token[1] : ''), 2);

        $soft_status_key = explode('id="soft_status_key" value="', $tokenResponse, 2);
        $soft_status_key = explode('" />', (isset($soft_status_key[1]) ? $soft_status_key[1] : ''), 2);

        // Restore the options set before this method call
        $this->setOptions($stored_options);
        return [
            'csrf_token' => isset($csrf_token[0]) ? trim($csrf_token[0]) : '',
            'soft_status_key' => isset($soft_status_key[0]) ? trim($soft_status_key[0]) : ''
        ];
    }
}
