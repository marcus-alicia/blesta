<?php

use Blesta\Core\Util\Common\Traits\Container;

/**
 * IP Unblocker plugin handler
 *
 * @package blesta
 * @subpackage blesta.plugins.ip_unblocker
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class IpUnblockerPlugin extends Plugin
{
    // Load traits
    use Container;

    /**
     * @var Monolog\Logger An instance of the logger
     */
    protected $logger;

    /**
     * @var type A list of class names for supported modules
     */
    private $supported_modules = ['cpanel', 'direct_admin'];

    public function __construct()
    {
        $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        Language::loadLang('ip_unblocker_plugin', null, dirname(__FILE__) . DS . 'language' . DS);

        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Input', 'Record']);
        }

        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Returns whether this plugin provides support for setting admin or client service tabs
     * @see Plugin::getAdminServiceTabs
     * @see Plugin::getClientServiceTabs
     *
     * @return bool True if the plugin supports service tabs, or false otherwise
     */
    public function allowsServiceTabs()
    {
        return true;
    }

    /**
     * Returns all tabs to display to a client when managing a service
     *
     * @param stdClass $service A stdClass object representing the selected service
     * @return array An array of tabs in the format of method => array where array contains:
     *
     *  - name (required) The name of the link
     *  - icon (optional) use to display a custom icon
     *  - href (optional) use to link to a different URL
     *      Example:
     *      array('methodName' => "Title", 'methodName2' => "Title2")
     *      array('methodName' => array('name' => "Title", 'icon' => "icon"))
     */
    public function getClientServiceTabs(stdClass $service)
    {
        $service_tabs = [];

        $module = $this->getModuleByService($service);
        if ($module && in_array($module->class, $this->supported_modules)) {
            $service_tabs = [
                'tabUnblockIp' => [
                    'name' => Language::_('IpUnblockerPlugin.unblock_ip', true),
                ]
            ];
        }

        return $service_tabs;
    }

    /**
     * Displays the custom tab defined for unblocking IPs
     *
     * @param stdClass $service An stdClass object representing the service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The content of the tab
     */
    public function tabUnblockIp(stdClass $service, array $get = null, array $post = null, array $files = null)
    {
        $this->view = new View();

        // Load the view at /plugins/ip_unblocker/views/default/unblock_ip.pdt
        $this->view->setView('tab_unblock_ip', 'IpUnblocker.default');

        // Load currency helper
        Loader::loadHelpers($this, ['Form', 'Html']);

        // Get requestor
        $requestor = $this->getFromContainer('requestor');
        if (!empty($post)) {
            $this->unblockIp($service, $requestor->ip_address);
        }

        // Make the IP address available to the view
        $this->view->set('ip_address', $requestor->ip_address);

        // Return the content of the view
        return $this->view->fetch();
    }

    /**
     * Unblocks an IP
     *
     * @param stdClass $service An stdClass object representing the service
     * @param string $ip_address The IP address to unblock
     */
    private function unblockIp(stdClass $service, $ip_address)
    {
        Loader::loadModels($this, ['Clients', 'ModuleManager']);

        // Get module info
        $module = $this->getModuleByService($service);
        $module_row = $this->ModuleManager->getRow($service->module_row_id);
        $meta = $module_row->meta;

        switch ($module->class) {
            case 'cpanel':
                // Make the unblock request to cPanel
                $response = $this->makeRequest(
                    ['action' => 'kill', 'ip' => $ip_address],
                    'http' . ($meta->use_ssl == '1' ? 's' : '') . '://'
                        . $meta->host_name . ':2087/cgi/configserver/csf.cgi',
                    'POST',
                    [CURLOPT_HTTPHEADER => ["Authorization: WHM " . $meta->user_name . ":" . $meta->key]]
                );

                // Set success message
                if ($response) {
                    $this->setMessage('success', Language::_('IpUnblockerPlugin.!success.unblock_ip', true));
                }
                break;
            case 'direct_admin':
                // Make the unblock request to Direct Admin
                $response = $this->makeRequest(
                    ['action' => 'kill', 'ip' => $ip_address],
                    'http' . ($meta->use_ssl == '1' ? 's' : '') . '://'
                        . $meta->host_name . ':' . $meta->port . '/CMD_PLUGINS_ADMIN/csf/index.raw',
                    'POST',
                    [
                        CURLOPT_USERAGENT => 'Blesta IP Unblocker',
                        CURLOPT_USERPWD => $meta->user_name . ':' . $meta->password,
                        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
                    ]
                );

                // Set success message
                if ($response) {
                    $this->setMessage('success', Language::_('IpUnblockerPlugin.!success.unblock_ip', true));
                }
                break;
        }
    }

    /**
     * Returns the module associated with a given service
     *
     * @param stdClass $service An stdClass object representing the selected service
     * @return mixed A stdClass object representing the module for the service
     */
    private function getModuleByService(stdClass $service)
    {
        return $this->Record->select('modules.*')->
            from('module_rows')->
            innerJoin('modules', 'modules.id', '=', 'module_rows.module_id', false)->
            where('module_rows.id', '=', $service->module_row_id)->
            fetch();
    }

    /**
     * Send an HTTP request.
     *
     * @param array $post The parameters to include in the request
     * @param string $url Specifies the url to invoke
     * @param string $method Http request method (GET, DELETE, POST)
     * @param array $curl_options A list of curl options
     * @return string An json formatted string containing the response
     */
    protected function makeRequest(array $post, $url, $method = 'GET', array $curl_options = [])
    {
        $ch = curl_init();

        // Set the request method and parameters
        switch (strtoupper($method)) {
            case 'GET':
            case 'DELETE':
                $url .= empty($post) ? '' : (substr_count($url, '?') < 1 ? '?' : '&') . http_build_query($post);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POST, 1);
            default:
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
                break;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 0);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        // Create new session cookies
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);

        // Check the Header
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Set additional curl options and overrides
        foreach ($curl_options as $curl_option => $value) {
            curl_setopt($ch, $curl_option, $value);
        }

        // Get response from the server.
        $response = curl_exec($ch);

        // Set curl errors
        $error = curl_error($ch);
        if ($error !== '') {
            $errorMessage = Language::_('IpUnblockerPlugin.!error.remote_curl', true, $error);
            $this->Input->setErrors(['login' => ['invalid' => $errorMessage]]);
            $this->logger->error($errorMessage);
            return;
        }

        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        return trim(substr($response, $curlInfo['header_size']));
    }
}
