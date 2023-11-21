<?php
namespace Blesta\Core\Util\GeoIp;

/**
 * Abstract GeoIP
 *
 * @package blesta
 * @subpackage blesta.core.Util.GeoIp
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractGeoIp
{
    /**
     * @var string The Ip address to use for all queries
     */
    private $ip_address = '';

    /**
     * Returns the 2-character country code where the IP resides
     *
     * @return string The 2-character country code where the IP resides
     */
    abstract public function getCountryCode();

    /**
     * Returns the name of the country where the IP resides
     *
     * @return string The name of the country where the IP resides
     */
    abstract public function getCountryName();

    /**
     * Fetches an array of information about the location of the IP address,
     * including longitude and latitude.
     *
     * @return array An array of information about the location of the IP address
     */
    abstract public function getLocation();

    /**
     * Get the region (e.g. state) of the given IP address.
     *
     * @return string The region the IP address resides in
     */
    abstract public function getRegion();

    /**
     * Get the organization or ISP that owns the current IP address. Requires a premium
     * database.
     *
     * @return string The organization the IP address belongs to
     */
    abstract public function getOrganization();

    /**
     * Returns the currently set IP address
     *
     * @return string The IP given in $ip, or the user's IP if $ip was null.
     */
    public function getIp()
    {
        return $this->ip_address;
    }

    /**
     * Sets the Ip address to use for all subsequent queries
     *
     * @param string $ip The Ip address to set
     */
    public function setIp($ip)
    {
        $this->ip_address = $ip;
    }
}
