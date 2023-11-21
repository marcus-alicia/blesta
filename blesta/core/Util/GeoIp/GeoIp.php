<?php
namespace Blesta\Core\Util\GeoIp;

use Loader;

/**
 * GeoIP v1 integration
 *
 * @package blesta
 * @subpackage blesta.core.Util.GeoIp
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GeoIp extends AbstractGeoIp
{
    /**
     * @var GeoIP The GeoIP database object
     */
    private $database;

    /**
     * Create a new GeoIP instance using the given database file
     *
     * @param string $database_file The full path to the database file
     */
    public function __construct($database_file)
    {
        $library_path = VENDORDIR . 'maxmind' . DS . 'geoip';

        if (!function_exists('geoip_db_avail')) {
            set_include_path(get_include_path() . PATH_SEPARATOR . $library_path);

            // Load library
            Loader::load($library_path . DS . 'geoipcity.inc');

            // Open the database file
            $this->database = geoip_open($database_file, GEOIP_STANDARD);
        }
    }

    /**
     * Attempts to close the open connection to the database file
     */
    public function __destruct()
    {
        try {
            if ($this->database) {
                geoip_close($this->database);
            }
        } catch (Exception $e) {
            // could not close the GeoIP database
        }
    }

    /**
     * Returns the 2-character country code where the IP resides
     *
     * @return string The 2-character country code where the IP resides
     */
    public function getCountryCode()
    {
        if (!$this->database) {
            return geoip_country_code_by_name($this->getIp());
        }
        return geoip_country_code_by_addr($this->database, $this->getIp());
    }

    /**
     * Returns the name of the country where the IP resides
     *
     * @return string The name of the country where the IP resides
     */
    public function getCountryName()
    {
        if (!$this->database) {
            return geoip_country_name_by_name($this->getIp());
        }
        return geoip_country_name_by_addr($this->database, $this->getIp());
    }

    /**
     * Fetches an array of information about the location of the IP address,
     * including longitude and latitude.
     *
     * @return array An array of information about the location of the IP address
     */
    public function getLocation()
    {
        if (!$this->database) {
            $locations = (array) geoip_record_by_name($this->getIp());
        } else {
            $locations = (array) geoip_record_by_addr($this->database, $this->getIp());
        }

        // UTF-8 encode the retrieved ISO 8859-1 strings
        foreach ($locations as $key => &$value) {
            $value = utf8_encode($value);
        }

        return $locations;
    }

    /**
     * Get the region (e.g. state) of the given IP address.
     *
     * @return string The region the IP address resides in
     */
    public function getRegion()
    {
        if (!$this->database) {
            return geoip_region_by_name($this->getIp());
        }
        return geoip_region_by_addr($this->database, $this->getIp());
    }

    /**
     * Get the organization or ISP that owns the IP address. Requires a premium
     * database.
     *
     * @return string The oraganization the IP address belongs to
     */
    public function getOrganization()
    {
        if (!$this->database) {
            return geoip_org_by_name($this->getIp());
        }
        return geoip_org_by_addr($this->database, $this->getIp());
    }
}
