<?php
include_once dirname(__FILE__) . DS . 'softaculous_installer.php';

class CpanelInstaller extends SoftactulousInstaller
{

    /**
     * Validates informations and runs a softaculous installation script on cPanel
     *
     * @param stdClass $service An object representing the cPanel service to execute a script for
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
            'user' => $serviceFields['cpanel_username'],
            'pass' => $serviceFields['cpanel_password'],
            'goto_uri' => '/'
        ];
        $hostName = isset($meta->host_name) ? $meta->host_name : '';
        $loginResponse = $this->makeRequest(
            $loginData,
            'https://' . $hostName . ':2083/login/',
            'POST'
        );

        if ($loginResponse == null) {
            $errorMessage = Language::_('SoftaculousPlugin.remote_error', true);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error($errorMessage);
            return;
        }

        $parsed = ['path' => ''];
        if (!empty($loginResponse->redirect_url)) {
            $parsed = parse_url($loginResponse->redirect_url);
        } elseif (!empty($loginResponse->url)) {
            $parsed = parse_url($loginResponse->url);
        }

        $path = trim(dirname($parsed['path']));
        $path = rtrim($path[0] == '/' ? $path : '/' . $path, '/');

        // Make the Login system
        $login = 'https://' . rawurlencode($serviceFields['cpanel_username']) . ':'
            . rawurlencode($serviceFields['cpanel_password']) . '@' . $meta->host_name . ':2083'
            . $path . '/softaculous/index.live.php';

        return $this->installScript(
            (!empty($serviceFields['cpanel_domain']) ? $serviceFields['cpanel_domain'] : ''),
            $client->email,
            $login,
            $configOptions
        );
    }
}
