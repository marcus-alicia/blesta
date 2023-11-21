<?php
/**
 * Order System Parent Controller
 *
 * @package blesta
 * @subpackage blesta.plugins.order
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class OrderController extends AppController
{
    public function preAction()
    {
        $this->structure->setDefaultView(APPDIR);
        parent::preAction();

        // Auto load language for the controller
        Language::loadLang(
            [Loader::fromCamelCase(get_class($this)), 'order_plugin'],
            null,
            dirname(__FILE__) . DS . 'language' . DS
        );

        // Override default view directory
        $this->view->view = 'default';
        $this->orig_structure_view = $this->structure->view;
        $this->structure->view = 'default';

        if (!is_numeric($this->Session->read('blesta_id'))
            && ($lanugage_setting = $this->Companies->getSetting(Configure::get('Blesta.company_id'), 'language'))
        ) {
            // Set the language for this session if clients are allowed
            $this->Session->write('blesta_language', $lanugage_setting->value);
        }
        $this->setClientLanguage(Configure::get('Blesta.language'));
    }

    /**
     * Retrieves the location of the given IP address
     *
     * @param string $ip_address The IP address
     * @param array $system_settings An array of the system settings (optional)
     * @return mixed False if geo IP is disabled or unavailable, otherwise an array containing:
     *  - location An array containing address information:
     *      - city
     *      - region
     *      - postal_code
     *      - country_name
     *      - latitude
     *      - longitude
     */
    protected function getGeoIp($ip_address, array $system_settings = null)
    {
        if (empty($ip_address)) {
            return false;
        }

        if (!isset($this->SettingsCollection)) {
            $this->components(['SettingsCollection']);
        }

        if (empty($system_settings)) {
            $system_settings = $this->SettingsCollection->fetchSystemSettings();
        }

        $geo_ip = [];
        if (isset($system_settings['geoip_enabled']) && $system_settings['geoip_enabled'] == 'true') {
            // Load GeoIP API
            $this->components(['Net']);
            if (!isset($this->NetGeoIp)) {
                $this->NetGeoIp = $this->Net->create('NetGeoIp');
            }

            try {
                $geo_ip = ['location' => $this->NetGeoIp->getLocation($ip_address)];
            } catch (Exception $e) {
                // IP address could not be determined
                return false;
            }

            return $geo_ip;
        }

        return false;
    }

    /**
     * Set the affiliate code in the current session
     *
     * @param string $code The affiliate code to use on order completions
     * @return bool True if the code has been added to the current session, false otherwise
     */
    protected function setAffiliateCode($code)
    {
        $this->uses(['Order.OrderAffiliates', 'Order.OrderAffiliateCompanySettings', 'Order.OrderAffiliateStatistics']);

        // Get affiliate
        $client_id = $this->Session->read('blesta_client_id');
        $affiliate = $this->OrderAffiliates->getByCode($code);

        if (empty($affiliate) || empty($code)) {
            return false;
        }

        // Update affiliate visits count
        if (!isset($_COOKIE['affiliate_code']) || ($_COOKIE['affiliate_code'] !== $code)) {
            $this->OrderAffiliateStatistics->registerVisit($affiliate->id);
        }

        if ((isset($affiliate->client_id) && $affiliate->client_id !== $client_id) || empty($client_id)) {
            // Get cookie TLD
            $cookie_tld = $this->OrderAffiliateCompanySettings->getSetting(
                Configure::get('Blesta.company_id'),
                'cookie_tld'
            );
            $cookie_tld = isset($cookie_tld->value) ? $cookie_tld->value : 0;

            // Save the affiliate code on the current session
            $cookie_tld = ($cookie_tld * 24 * 60 * 60);
            setcookie(
                'affiliate_code',
                $code,
                time() + $cookie_tld,
                ini_get('session.cookie_path'),
                ini_get('session.cookie_domain'),
                ini_get('session.cookie_secure'),
                ini_get('session.cookie_httponly')
            );

            return true;
        }

        return false;
    }
}

require_once dirname(__FILE__) . DS . 'order_form_controller.php';
require_once dirname(__FILE__) . DS . 'order_affiliate_controller.php';
