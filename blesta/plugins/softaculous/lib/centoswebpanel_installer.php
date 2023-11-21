<?php
include_once dirname(__FILE__) . DS . 'softaculous_installer.php';

class CentoswebpanelInstaller extends SoftactulousInstaller
{
    /**
     * Validates informations and runs a softaculous installation script on CentOS Web Panel
     *
     * @param stdClass $service An object representing the CentOS Web Panel service to execute a script for
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

        // Login and get the cookies
        $autoLoginData = [
            'action' => 'list',
            'key' => isset($meta->api_key) ? $meta->api_key : '',
            'user' => isset($serviceFields['centoswebpanel_username']) ? $serviceFields['centoswebpanel_username'] : '',
            'module' => 'softaculous'
        ];
        $hostName = isset($meta->host_name) ? $meta->host_name : '';
        $port = isset($meta->port) ? $meta->port : '';
        $autoLoginUrl = 'https://' . $hostName . ':' . $port . '/v1/user_session';
        $autoLoginResponse = $this->makeRequest($autoLoginData, $autoLoginUrl, 'POST');
        if ($autoLoginResponse == null || !isset($autoLoginResponse->msj->details[0]->token)) {
            return;
        }
        $token = $autoLoginResponse->msj->details[0]->token;

        if (strtolower($autoLoginResponse->status) == 'error') {
            $errorMessage = Language::_('SoftaculousPlugin.remote_error_message', true, $autoLoginResponse->msj);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error($errorMessage);
            return;
        }

        $loginData = ['username' => $serviceFields['centoswebpanel_username'], 'token' => $token];
        $loginResponse = $this->makeRequest(
            $loginData,
            'https://' . $hostName . ':2083/' . $serviceFields['centoswebpanel_username'] . '/',
            'POST'
        );
        if ($loginResponse == null) {
            $errorMessage = Language::_('SoftaculousPlugin.remote_error', true);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error($errorMessage);
            return;
        }

        return $this->installScript(
            (!empty($serviceFields['centoswebpanel_domain']) ? $serviceFields['centoswebpanel_domain'] : ''),
            $client->email,
            $loginResponse->redirect_url,
            $configOptions
        );
    }
}
