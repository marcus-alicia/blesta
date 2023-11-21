<?php
Loader::load(COMPONENTDIR . "modules" . DS . "module_field.php");
Loader::load(COMPONENTDIR . "modules" . DS . "module_fields.php");

/**
 * Fraud Detect Interface
 *
 * @package blesta
 * @subpackage blesta.plugins.order.components.antifraud
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface FraudDetect
{

    /**
     * Sets key/value pair options for initializing the fraud detection
     *
     * @param array An array of key/value pairs
     */
    public function __construct(array $options);

    /**
     * Returns ModuleFields object containing all settings for the antifraud component
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields
     */
    public function getSettingFields($vars = null);

    /**
     * Verifies the given data passes fraud detection
     *
     * @param array An array of key/value pairs including:
     *  - ip The user's IP address
     *  - email The user's email address
     *  - address1 The user's address line 1
     *  - address2 The user's address line 2
     *  - city The user's city
     *  - state The user's state ISO 3166-2 alpha-numeric subdivision code
     *  - country The user's country ISO 3166-1 alpha2 country code
     *  - zip The user's zip/postal code
     *  - phone The user's primary phone number
     * @return string The result of verify input, one of either:
     *  - allow Data is not fraudulent
     *  - review Data may be fraudulent, requires manual review
     *  - reject Data is fraudulent
     */
    public function verify($data);

    /**
     * Returns fraud details to store for the last verify request
     *
     * @return array An array of key/value pairs
     * @see FraudDetect::verify()
     */
    public function fraudDetails();
}
