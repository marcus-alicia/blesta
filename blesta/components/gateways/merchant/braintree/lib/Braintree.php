<?php
/**
 * Braintree PHP Library
 * Creates class_aliases for old class names replaced by PSR-4 Namespaces
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'autoload.php');

if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Braintree_Exception('PHP version >= 5.4.0 required');
}

#
# Renamed 'Braintree' class to 'BraintreeApi' to circumvent duplicate class names with the Blesta gateway
#
class BraintreeApi {
    public static function requireDependencies() {
        $requiredExtensions = ['xmlwriter', 'openssl', 'dom', 'hash', 'curl'];
        foreach ($requiredExtensions AS $ext) {
            if (!extension_loaded($ext)) {
                throw new Braintree_Exception('The Braintree library requires the ' . $ext . ' extension.');
            }
        }
    }
}

BraintreeApi::requireDependencies();
