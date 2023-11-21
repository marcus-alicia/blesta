<?php

use Blesta\Core\Util\Common\Traits\Container;
use Blesta\Core\Util\GeoIp\GeoIp;
use Blesta\Core\Util\GeoIp\GeoIp2;

/**
 * NetGeoIP component that wraps Maxmind's GeoIP system. Requires mbstring
 * extension to be enabled with PHP (due to poor coding standards on MaxMind's
 * part).
 *
 * @package blesta
 * @subpackage blesta.components.net.net_geo_ip
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class NetGeoIp
{
    // Load traits
    use Container;

    /**
     * @var AbstractGeoIp The API interface through which to fetch data
     */
    private $api;

    /**
     * @var string The expected path to the GeoIP database
     */
    private $geo_ip_db_filename = 'GeoLiteCity.dat';

    /**
     * @var string The expected path to the GeoIP database
     */
    private $geo_ip2_db_filename = 'GeoLite2-City.mmdb';

    /**
     * Create a new GeoIP instance using the given database file
     */
    public function __construct()
    {
        // Otherwise use the best GeoIP database we have in the uploads directory
        Loader::loadComponents($this, ['SettingsCollection']);

        $system_settings = $this->SettingsCollection->fetchSystemSettings();
        if (isset($system_settings['uploads_dir'])) {
            $upload_path = $system_settings['uploads_dir'] . 'system' . DS;
            $geo_ip_db_path = $upload_path . $this->geo_ip_db_filename;
            $geo_ip2_db_path = $upload_path . $this->geo_ip2_db_filename;

            // Use the latest GeoIP database, or fallback to the old one if available
            if (file_exists($geo_ip2_db_path)) {
                $this->api = new GeoIp2($geo_ip2_db_path);
            } elseif (file_exists($geo_ip_db_path)) {
                $this->api = new GeoIp($geo_ip_db_path);
            }
        }
    }

    /**
     * Returns the 2-character country code where the IP resides
     *
     * @param string $ip The IP address to lookup, if null will use user's IP
     * @return string The 2-character country code where the IP resides
     * @see NetGeoIp::getLocation()
     */
    public function getCountryCode($ip = null)
    {
        $this->setIp($ip);
        return $this->call('getCountryCode');
    }

    /**
     * Returns the name of the country where the IP resides
     *
     * @param string $ip The IP address to lookup, if null will use user's IP
     * @return string The name of the country where the IP resides
     * @see NetGeoIp::getLocation()
     */
    public function getCountryName($ip = null)
    {
        $this->setIp($ip);
        return $this->call('getCountryName');
    }

    /**
     * Fetches an array of information about the location of the IP address,
     * including longitude and latitude.
     *
     * @param string $ip The IP address to lookup, if null will use user's IP
     * @return array An array of information about the location of the IP address
     */
    public function getLocation($ip = null)
    {
        $this->setIp($ip);
        return $this->call('getLocation');
    }

    /**
     * Get the region (e.g. state) of the given IP address.
     *
     * @param string $ip The IP address to lookup, if null will use user's IP
     * @return string The region the IP address resides in
     * @see NetGeoIp::getLocation()
     */
    public function getRegion($ip = null)
    {
        $this->setIp($ip);
        return $this->call('getRegion');
    }

    /**
     * Get the organization or ISP that owns the IP address. Requires a premium
     * database.
     *
     * @param string $ip The IP address to lookup, if null will use user's IP
     * @return string The oraganization the IP address belongs to
     */
    public function getOrg($ip = null)
    {
        $this->setIp($ip);
        return $this->call('getOrganization');
    }

    /**
     * Gets the name of the database file expected for the given version
     *
     * @param string $version The GeoIp version to get the filename for
     * @return string The name of the database file
     */
    public function getGeoIpDatabaseFilename($version = '2')
    {
        return $version == '1' ? $this->geo_ip_db_filename : $this->geo_ip2_db_filename;
    }

    /**
     * Sets the IP address for the API
     *
     * @param string $ip The IP to set (optional)
     */
    private function setIp($ip = null)
    {
        return $this->call('setIp', $this->currentIp($ip));
    }

    /**
     * Returns the currently set IP address
     *
     * @param string $ip If non-null, will be the returned value, else will use user's IP
     * @return string The IP given in $ip, or the user's IP if $ip was null.
     */
    private function currentIp($ip = null)
    {
        if ($ip !== null) {
            return $ip;
        }

        $requestor = $this->getFromContainer('requestor');
        return $requestor->ip_address;
    }

    /**
     * Makes a call to the API
     *
     * @param string $method The method of the API to call
     * @param mixed ... Any additional arguments to pass to the API call for this method
     * @return mixed The return value for the API call, or an empty string if no API call can be made
     */
    private function call($method)
    {
        if (!$this->api) {
            return '';
        }

        // Fetch the arguments, but ignore the first one since it's the $method
        $args = func_get_args();
        array_shift($args);

        return call_user_func_array([$this->api, $method], $args);
    }
}
