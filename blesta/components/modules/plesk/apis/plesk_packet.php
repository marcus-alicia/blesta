<?php
/**
 * Abstract class that all Plesk API commands must extend
 *
 * @copyright Copyright (c) 2013, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @package plesk
 */
abstract class PleskPacket
{
    /**
     * @var SimpleXMLElement The XML packet
     */
    private $packet;
    /**
     * @var string The container for this command
     */
    private $container;
    /**
     * @var string The API RPC version
     */
    private $api_version;

    /**
     * Retrieves the earliest version this API command supports
     *
     * @return string The earliest API RPC version supported
     */
    abstract public function getEarliestVersion();

    /**
     * Builds the packet container for the API command
     */
    abstract protected function buildContainer();

    /**
     * Initializes the Plesk Packet
     *
     * @param string $api_version The version of the API to use
     */
    public function __construct($api_version)
    {
        $this->api_version = $api_version;
        $this->init();
    }

    /**
     * Initializes the packet XML
     *
     * @param string $api_version The version of the API to use
     */
    private function init()
    {
        $str = '<packet' . (empty($this->api_version) ? '' : ' version="' . $this->api_version . '"') . ' />';
        $xml =<<<XML
<?xml version="1.0" encoding="UTF-8"?>
{$str}
XML;
        $this->packet = new SimpleXMLElement($xml);
        $this->resetContainer();
    }

    /**
     * Retrieves the container path
     *
     * @return string The container path
     */
    final public function getContainer()
    {
        return $this->container;
    }

    /**
     * Sets the container path
     *
     * @param string $container The container path relative to the base packet
     */
    final protected function setContainer($container)
    {
        $this->container = '/packet' . $container;
    }

    /**
     * Resets the container path to the base path
     */
    public function resetContainer()
    {
        $this->container = '/packet';
    }

    /**
     * Adds an XML element to the packet at the given $path. If multiple items exist at the given path,
     * all of them will be updated to include the given $elements
     *
     * @param array $elements An array of key/value pairs representing content to include in the packet
     * @param string $path The path within the packet to add the elements to (optional, defaults to the packet itself)
     * @return mixed A SimpleXMLElement that was created, or null if no elements were added
     */
    public function insert(array $elements = [], $path = '/packet')
    {
        $path = (empty($path) ? '/packet' : $path);

        if (($element = $this->packet->xpath($path))) {
            foreach ($element as $xml_element) {
                $child = $this->buildElements($elements, $xml_element);
            }
            return (isset($child) ? $child : null);
        }
        return null;
    }

    /**
     * Retrieves the packet
     *
     * @return string The XML packet
     */
    public function fetch()
    {
        return $this->packet->asXML();
    }

    /**
     * Resets the packet back to its original state
     */
    public function reset()
    {
        $this->init();
        $this->buildContainer();
    }

    /**
     * Adds the given elements to the packet
     *
     * @param array $elements An array of key/value pairs representing content to include in the packet
     * @param SimpleXMLElement $element An XML child to add XML elements under
     */
    private function buildElements(array $elements = [], SimpleXMLElement $element = null)
    {
        if (empty($elements)) {
            return $element;
        }

        if ($element === null) {
            $element = $this->packet;
        }

        foreach ($elements as $key => $value) {
            if (is_array($value)) {
                $child = $element->addChild($key);
                $this->buildElements($value, $child);
            } else {
                // Set only the container if a value is explicitly null
                if ($value === null) {
                    $element->addChild($key);
                } else {
                    $element->addChild($key, str_replace('&', '&amp;', $value));
                }
            }
        }
        return $element;
    }
}
