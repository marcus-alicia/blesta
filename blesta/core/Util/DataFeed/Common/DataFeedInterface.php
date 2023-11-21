<?php
namespace Blesta\Core\Util\DataFeed\Common;

/**
 * Data feed interface
 *
 * @package blesta
 * @subpackage blesta.core.Util.DataFeed.Common
 * @copyright Copyright (c) 2022, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface DataFeedInterface
{
    /**
     * Retrieves any errors associated with the data feed
     *
     * @return array An array of errors
     */
    public function errors();

    /**
     * Returns the name of the data feed
     *
     * @return string The name of the data feed
     */
    public function getName();

    /**
     * Returns the description of the data feed
     *
     * @return string The description of the data feed
     */
    public function getDescription();

    /**
     * Executes and returns the result of a given endpoint
     *
     * @param string $endpoint The endpoint to execute
     * @param array $vars An array containing the feed parameters
     * @return mixed The data feed response
     */
    public function get($endpoint, array $vars = []);

    /**
     * Sets options for the data feed
     *
     * @param array $options An array of options
     */
    public function setOptions(array $options);

    /**
     * Gets a list of the options input fields
     *
     * @param array $vars An array containing the posted fields
     * @return InputFields An object representing the list of input fields
     */
    public function getOptionFields(array $vars = []);
}
