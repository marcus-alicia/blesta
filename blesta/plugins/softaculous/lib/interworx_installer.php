<?php
include_once dirname(__FILE__) . DS . 'softaculous_installer.php';

class InterworxInstaller extends SoftactulousInstaller
{

    /**
     * Validates informations and runs a softaculous installation script on Interworx
     *
     * @param stdClass $service An object representing the Interworx service to execute a script for
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

        // Authenticate in to Siteworx account
        $loginData = [
            'email' => $serviceFields['interworx_email'],
            'password' => $serviceFields['interworx_password'],
            'domain' => $serviceFields['interworx_domain']
        ];
        $hostUrl = ($meta->use_ssl == 'true' ? 'https' : 'http') . '://' . $meta->host_name . ':' . $meta->port . '/siteworx/index?action=login';

        $loginResponse = $this->makeRequest(
            $loginData,
            $hostUrl,
            'POST'
        );

        if ($loginResponse == null) {
            $errorMessage = Language::_('SoftaculousPlugin.remote_error', true);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error($errorMessage);
            return;
        }

        // Get control panel path
        $parsed = ['path' => ''];
        if (!empty($loginResponse->redirect_url)) {
            $parsed = parse_url($loginResponse->redirect_url);
        } elseif (!empty($loginResponse->url)) {
            $parsed = parse_url($loginResponse->url);
        }

        $path = trim(dirname($parsed['path']));
        $path = rtrim($path[0] == '/' ? $path : '/' . $path, '/');

        // Interworx by default creates a robots.txt file on new accounts, making it necessary to overwrite the existing files
        $configOptions['overwrite_existing'] = 1;

        // Install script
        $login = ($meta->use_ssl == 'true' ? 'https' : 'http') . '://'
            . $meta->host_name . ':' . $meta->port . '/siteworx/softaculous/';

        return $this->installScript(
            isset($serviceFields['interworx_domain']) ? $serviceFields['interworx_domain'] : '',
            $client->email,
            $login,
            $configOptions
        );
    }
}
