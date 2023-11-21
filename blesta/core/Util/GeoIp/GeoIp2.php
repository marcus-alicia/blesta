<?php
namespace Blesta\Core\Util\GeoIp;

use GeoIp2\Database\Reader;
use Exception;
use Throwable;

/**
 * GeoIP v2 integration
 *
 * @package blesta
 * @subpackage blesta.core.Util.GeoIp
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class GeoIp2 extends AbstractGeoIp
{
    /**
     * @var Reader The GeoIP2 database reader to use for querying to database
     */
    private $reader;

    /**
     * @var array An array of records retrieved from the database keyed by IP addresses
     */
    private $records = [];

    /**
     * Create a new GeoIP instance using the given database file
     *
     * @param string $database_file The full path to the database file
     */
    public function __construct($database_file)
    {
        $this->reader = new Reader($database_file);
    }

    /**
     * Returns the 2-character country code where the IP resides
     *
     * @return string The 2-character country code where the IP resides
     */
    public function getCountryCode()
    {
        return isset($this->records[$this->getIp()]) ? $this->records[$this->getIp()]->country->isoCode : '';
    }

    /**
     * Returns the name of the country where the IP resides
     *
     * @return string The name of the country where the IP resides
     */
    public function getCountryName()
    {
        return isset($this->records[$this->getIp()]) ? $this->records[$this->getIp()]->country->name : '';
    }

    /**
     * Fetches an array of information about the location of the IP address,
     * including longitude and latitude.
     *
     * @return array An array of information about the location of the IP address
     */
    public function getLocation()
    {
        if (!isset($this->records[$this->getIp()])) {
            return false;
        }

        return [
            'country_code' => $this->getCountryCode(),
            'country_code3' => '',
            'country_name' => $this->getCountryName(),
            'region' => $this->getRegion(),
            'city' => $this->records[$this->getIp()]->city->name,
            'postal_code' => $this->records[$this->getIp()]->postal->code,
            'latitude' => $this->records[$this->getIp()]->location->latitude,
            'longitude' => $this->records[$this->getIp()]->location->longitude,
            'area_code' => '',
            'dma_code' => '',
            'metro_code' => $this->records[$this->getIp()]->location->metroCode,
        ];
    }

    /**
     * Get the region (e.g. state) of the given IP address.
     *
     * @return string The region the IP address resides in
     */
    public function getRegion()
    {
        return isset($this->records[$this->getIp()])
            ? $this->records[$this->getIp()]->mostSpecificSubdivision->isoCode
            : '';
    }

    /**
     * Would get the organization or ISP that owns the current IP address. However this information
     * is not available from the GeoIp2 city model
     *
     * @return string
     */
    public function getOrganization()
    {
        return '';
    }

    /**
     * Sets the Ip address to use for all suybsequent queries
     *
     * @param string $ip The Ip address to set
     */
    public function setIp($ip)
    {
        if (!isset($this->records[$ip])) {
            try {
                $this->records[$ip] = $this->reader->city($ip);
            } catch (Throwable $e) {
                $ip = '';
            }
        }

        parent::setIp($ip);
    }
}
