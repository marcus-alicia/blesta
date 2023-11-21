<?php
include_once dirname(__FILE__) . DS . 'softaculous_installer.php';
class PleskInstaller extends SoftactulousInstaller
{
    /**
     * Validates informations and runs a softaculous installation script on Plesk
     *
     * @param stdClass $service An object representing the Plesk service to execute a script for
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
        $loginData = [
            'login_name' => isset($meta->username) ? $meta->username : '',
            'passwd' => isset($meta->password) ? $meta->password : ''
        ];
        $hostName = isset($meta->host_name) ? $meta->host_name : '';
        $port = isset($meta->port) ? $meta->port : '';
        $loginUrl = 'https://' . $hostName . ':' . $port . '/login_up.php3';
        $this->makeRequest($loginData, $loginUrl, 'POST');

        // Set the domain to manage
        $this->cookie = (!empty($this->cookie) ? $this->cookie . ';' : '')
            . 'softdomid=' . $serviceFields['plesk_webspace_id'];

        return $this->installScript(
            (!empty($serviceFields['plesk_domain']) ? $serviceFields['plesk_domain'] : ''),
            $client->email,
            'https://' . $hostName . ':' . $port . '/modules/softaculous/index.php',
            $configOptions
        );
    }
}