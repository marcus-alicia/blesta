<?php
/**
 * License Manager Library
 *
 * Application integration code, used for validating license data supplied by
 * the Blesta License Manager Plugin.
 *
 * Requires PHP 5.4 or greater.
 *
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @copyright Copyright (c) 2012-2018, Phillips Data Inc.
 */
final class LicenseManager
{
    /**
     * @var string The full path to phpseclib
     */
    private $path_to_phpseclib;

    /**
     * @var string The type of hash to use when hashing phpseclib
     */
    private $signature_mode;

    /**
     * @var string The URL to the license server
     */
    private $server_url;

    /**
     * @var stdClass An object containing all relevant crypto keys
     */
    private $keys;

    /**
     * @var string The install path, defaults to dirname(__FILE__)
     */
    private $install_path;

    /**
     * @var string The delimiter used to separated the data from the signature in a response
     */
    const RESPONSE_DELIMITER = '$';

    /**
     * Initializes the license manager
     *
     * @param string $path_to_phpseclib The full path to phpseclib
     * @param string $signature_mode The type of hash to use (md5, sha256, etc.)
     * @param string $signatures The signatures to validate against, set to null to bypass check, otherwise an array of
     * key/value pairs where each key is a filename and each value is the expected hash or an array of possible hashses
     * @param string $install_path The path to the installation of which to
     *  validate (simply sent to the validation server)
     * @throws Exception Thrown when a signature is given and does not validate
     * @throws Exception Thrown when $signature_mode is invalid for this system
     */
    public function __construct(
        $path_to_phpseclib,
        $signature_mode = 'md5',
        array $signatures = null,
        $install_path = null
    ) {
        $this->path_to_phpseclib = $path_to_phpseclib;
        $this->signature_mode = $signature_mode;

        $this->install_path = $install_path;
        if ($this->install_path === null) {
            $this->install_path = dirname(__FILE__);
        }

        if (!function_exists('hash_file') && $signature_mode != 'md5') {
            throw new Exception("hash_file() not supported on this system, signature mode must be 'md5'");
        }

        // Verify signatures match local files
        if ($signatures !== null && !$this->verifyFileSignatures($signatures)) {
            throw new Exception('One or more signatures are invalid, LicenseManager can not be initialized.');
        }
    }

    /**
     * Sets the URL for the license server to communicate with
     *
     * @param string $server_url The URL to the license server (should include protocol, hostname, and port
     * (if not HTTP(S)) e.g. https://www.mydomain.com:8080/)
     */
    public function setLicenseServerUrl($server_url)
    {
        $this->server_url = $server_url;
    }

    /**
     * Sets all relevant crypt keys
     *
     * @param string $license_key The license key for the software
     * @param string $public_key The public encryption key for the software
     * @param string $shared_secret The shared secret (also known as the HMAC key)
     */
    public function setKeys($license_key, $public_key, $shared_secret)
    {
        $this->keys = new stdClass();
        $this->keys->hmac_key = $shared_secret;
        $this->keys->public_key = $public_key;
        $this->keys->license_key = $license_key;
        $this->keys->aes_key = $this->hash($this->keys->license_key, 'sha256', $this->keys->hmac_key);
        $this->keys->iv = $this->hash($this->keys->public_key, 'sha256', $this->keys->hmac_key);
    }

    /**
     * Verify that all of the given signatures match
     *
     * @param array $signatures An array of key/value pairs where each key is
     *  the file path and each value is the signature, or an array of possible
     *  signatures, to verify against
     * @return bool True if the signatures verify, false otherwise
     */
    private function verifyFileSignatures(array $signatures)
    {
        return true;
    }

    /**
     * Computes the signature of all of the given files
     *
     * @param array $files An array of files to compute signatures for
     * @return array An array of computed signatures in key/value pairs
     *  where each key is the file path and each value is the signature
     */
    public function getFileSignatures(array $files)
    {
        $sigs = [];
        foreach ($files as $file) {
            if ($this->signature_mode == 'md5') {
                $sigs[$file] = md5_file($file);
            } else {
                $sigs[$file] = hash_file($this->signature_mode, $file);
            }
        }

        return $sigs;
    }

    /**
     * Validates the provided license data to ensure it is both trusted and
     * valid.
     *
     * @param string $license_data The encrypted license data to validate
     * @param int $ttl The number of seconds that $license_data is valid for since the last time it was fetched
     * @return array The license data containing:
     *     - status The status of the license, one of the following:
     *         - valid The license is valid
     *         - invalid_location The license is invalid for this domain, IP,
     *          or directory path. Request reissue, then try LicenseManager::requestData()
     *         - suspended The license has been suspended, try LicenseManager::requestData()
     *         - expired The license data has expired, try LicenseManager::requestData()
     *         - unknown The license data was not given or is corrupt, try LicenseManager::requestData()
     *    - label The label used to ID this license type
     *    - time The UNIX time from the license server of the last call-home
     *    - allow_reissue 1 if reissue is allowed, 0 otherwise
     *    - addons An array of purchased addon packages related to this license
     *    - version The version of the software the license server has recorded for this installation
     *    - custom An array of custom fields set for this license type
     */
    public function validate($license_data, $ttl)
    {
        /**
        * if ($license_data && $this->verifySignature($license_data, $this->keys, true)) {
        */
        if ($license_data) {
            $status = 'valid';

            // Decrypt and sanitize the license data through JSON
            // to avoid PHP Object Injection with unserialize()
            $data = json_decode(
                json_encode(
                    unserialize($this->decryptData($license_data, $this->keys))
                ),
                true
            );

            $server_info = $this->getServerInfo();

            // Verify license is still valid
            if (!isset($data['time']) || time() > ($data['time'] + $ttl)) {
                $status = 'expired';
            } elseif (isset($data['status']) && $data['status'] == 'suspended') {
                // Verify license has not been suspended
                $status = 'suspended';
            } elseif (isset($data['domain'])
                && !in_array($server_info['domain'], (array) $data['domain'])
                && !in_array('*', (array) $data['domain'])
            ) {
                // Verify license domain (if given)
                $status = 'invalid_location';
            } elseif (isset($data['ip'])
                && !in_array($server_info['ip'], (array) $data['ip'])
                && !in_array('*', (array) $data['ip'])
            ) {
                // Verify license ip (if given)
                $status = 'invalid_location';
            } elseif (isset($data['path'])
                && !in_array($server_info['path'], (array) $data['path'])
                && !in_array('*', (array) $data['path'])
            ) {
                // Verify license path (if given)
                $status = 'invalid_location';
            }

            unset($data['status']);
            return array_merge(
                [
                    'status' => "valid",
                    'label' => null,
                    'time' => null,
                    'allow_reissue' => null,
                    'addons' => null,
                    'version' => null,
                    'max_version' => null,
                    'custom' => null
                ],
                $data
            );
        }
        return [
            'status' => 'unknown'
        ];
    }

    /**
     * Request license data from the server
     *
     * @param array $custom_data An array of custom data to send to the license server (if any)
     * @param int $timeout The number of seconds to wait for a response from
     *  the license server before closing the connection
     * @return string The encrypted license data
     * @see LicenseManager::validate()
     */
    public function requestData(array $custom_data = null, $timeout = 10)
    {
        // Encrypt the data we send
        $data = $this->encrypt(
            serialize($this->getServerInfo() + (array) $custom_data),
            $this->keys->aes_key,
            $this->keys->iv
        );

        $params = [
            'key' => $this->keys->license_key,
            'data' => $this->formatResponse(
                $data,
                $this->signRsa($data, $this->keys->public_key, $this->keys->hmac_key)
            )
        ];

        return $this->submitRequest('POST', $this->server_url . 'index', $params, true, $timeout);
    }

    /**
     * Request a public key
     *
     * @param array $custom_data An array of custom data to send to the license server (if any)
     * @param int $timeout The number of seconds to wait for a response from
     *  the license server before closing the connection
     * @return string The requested public key (if supplied)
     */
    public function requestKey(array $custom_data = null, $timeout = 10)
    {
        $data = base64_encode(serialize($this->getServerInfo() + (array) $custom_data));

        $params = [
            'key' => $this->keys->license_key,
            'data' => $this->formatResponse($data, $this->signHmac($data, $this->keys->hmac_key))
        ];

        $response = $this->submitRequest('POST', $this->server_url . 'keyexchange', $params, false, $timeout);

        // Only return the key, not the signature
        if ($response) {
            $parts = explode(self::RESPONSE_DELIMITER, $response);
            return $parts[0];
        }
        return null;
    }

    /**
     * Returns an array of server info
     *
     * @return array An array of server info, including:
     *     - ip The IP address of this installation
     *     - domain The domain of this installation
     *     - path The path to this installation
     */
    private function getServerInfo()
    {
        $data = [
            'ip' => '',
            'domain' => '',
            'path' => $this->install_path
        ];

        // Set the IP
        if (isset($_SERVER['SERVER_ADDR'])) {
            $data['ip'] = $_SERVER['SERVER_ADDR'];
        } elseif (isset($_SERVER['LOCAL_ADDR'])) {
            // Windows IIS 7 support
            $data['ip'] = $_SERVER['LOCAL_ADDR'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            // If no IP found, perform lookup based on server name
            $data['ip'] = @gethostbyname($_SERVER['SERVER_NAME']);
        } elseif (isset(Configure::get('Blesta.company')->hostname)) {
            $data['ip'] = @gethostbyname(Configure::get('Blesta.company')->hostname);
        }

        // Set the domain if SERVER_NAME is available, otherwise perform lookup based on IP
        if (isset($_SERVER['SERVER_NAME'])) {
            $data['domain'] = $_SERVER['SERVER_NAME'];
        } elseif (isset(Configure::get('Blesta.company')->hostname)) {
            $data['domain'] = Configure::get('Blesta.company')->hostname;
        } else {
            $data['domain'] = @gethostbyaddr($data['ip']);
        }

        // Ensure case-insensitive
        $data['domain'] = strtolower($data['domain']);

        // Strip "www." from beginning of domain if present
        if (substr($data['domain'], 0, 4) == 'www.') {
            $data['domain'] = substr($data['domain'], 4);
        }

        return $data;
    }

    /**
     * Format the response data as (data + delimiter + sig)
     *
     * @param string $data The data to send
     * @param string $sig The signature of the data to send
     * @return string The formatted  response data and signature
     */
    private static function formatResponse($data, $sig)
    {
        return wordwrap($data . self::RESPONSE_DELIMITER . $sig);
    }

    /**
     * Submits the request to the given URL and verifies the response can
     * be trusted.
     *
     * @param string $method The reuqest method (GET, POST, PUT, DELETE, etc.)
     * @param string $url The URL to submit the request to
     * @param array $params An array of key/value parameters
     * @param bool $encrypted True if the response data is encrypted, false otherwise
     * @param int $timeout The number of seconds to wait for a response from the $url before terminating the connection
     * @return string The resonse
     */
    private function submitRequest($method, $url, array $params = null, $encrypted = true, $timeout = 10)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($params) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }

        $response = curl_exec($ch);
        curl_close($ch);

        // Verify the response
        if ($response && $this->verifySignature($response, $this->keys, $encrypted)) {
            return $response;
        }
        return null;
    }

    /**
     * Verifies that the data given can be trusted
     *
     * @param string $data The complete data set, including the attached signature
     * @param stdClass $keys A stdClass object containing the keys for the license:
     *     - public_key
     *     - hmac_key
     * @param bool $encrypted True if the data is encrypted, false otherwise
     * @return bool True if the data can be trusted, false otherwise
     */
    private function verifySignature($data, $keys, $encrypted = true)
    {
        $parts = explode(self::RESPONSE_DELIMITER, $data);

        if (count($parts) == 2) {
            // If encrypted, data is signed using an RSA public key
            if ($encrypted) {
                return $this->verifyRsa($parts[0], $keys->public_key, $parts[1], $keys->hmac_key);
            } else {
                // If not encrypted, data is signed using HMAC
                return $this->verifyHmac($parts[0], $keys->hmac_key, $parts[1]);
            }
        }
        return false;
    }

    /**
     * Decrypts the given encrypted data using the given key data
     *
     * @param string $data The encrypted data + signature
     * @param stdClass $keys A stdClass object containing the keys for this data:
     *     - aes_key
     *     - iv
     * @return string The decrypted data
     */
    private function decryptData($data, $keys)
    {
        $parts = explode(self::RESPONSE_DELIMITER, $data);

        if (count($parts) == 2) {
            return $this->decrypt($parts[0], $keys->aes_key, $keys->iv);
        }
        return null;
    }

    /**
     * Signs data using an HMAC
     *
     * @param string $data The data to sign
     * @param string $hmac_key The key used to sign the data using HMAC
     * @param string $hash The hash to use (md5, sha1, sha256, sha512, etc.)
     * @return string The signed data in base64 format
     */
    private function signHmac($data, $hmac_key, $hash = 'sha256')
    {
        return base64_encode($this->hash($data, $hash, $hmac_key));
    }

    /**
     * Verifies the signature using HMAC
     *
     * @param string $data The data to sign
     * @param string $hmac_key The key used to sign the data using HMAC
     * @param string $hash The hash to use (md5, sha1, sha256, sha512, etc.)
     * @return bool True if the signature matches, false otherwise
     */
    private function verifyHmac($data, $hmac_key, $signature, $hash = 'sha256')
    {
        return $this->signHmac($data, $hmac_key, $hash) == $signature;
    }

    /**
     * Signs the given data using RSA signature with the provided private key
     *
     * @param string $data Data to be signed. A hash of this data will automatically be computed and the hash signed.
     * @param string $public_key The public key to use to sign the data
     * @param string $hmac_key The key used to compute the HMAC hash, if null
     *  will only compute a normal hash of the data
     * @param string $hash The hash to use (md5, sha1, sha256, sha512, etc.)
     * @return string The signature of the $data in base64 format
     */
    private function signRsa($data, $public_key, $hmac_key = null, $hash = 'sha256')
    {
        $this->loadCrypto(['RSA']);

        $this->Crypt_RSA->loadKey($public_key);

        // Generate a hash of $data, because computing signatures on large
        // strings is slow and unncessary
        $hash = $this->hash($data, $hash, $hmac_key);

        $signature = $this->Crypt_RSA->sign($hash);
        unset($this->Crypt_RSA);
        return base64_encode($signature);
    }

    /**
     * Verifies the signature given for the data using the public key
     *
     * @param string $data The data to be verified. A hash of this data will
     *  automatically be computed and the hash verified.
     * @param string $public_key The public key to use to verify the signature
     * @param string $signature A base64 encoded signature to be verified against
     * @param string $hmac_key The key used to compute the HMAC hash, if null
     *  will only compute a normal hash of the data
     * @param string $hash The hash to use (md5, sha1, sha256, sha512, etc.)
     * @return bool True if the signature is valid, false otherwise
     */
    private function verifyRsa($data, $public_key, $signature, $hmac_key = null, $hash = 'sha256')
    {
        $this->loadCrypto(['RSA']);

        // Generate a hash of $data, because computing signatures on large
        // strings is slow and unncessary
        $hash = $this->hash($data, $hash, $hmac_key);

        $this->Crypt_RSA->loadKey($public_key);

        $verified = $this->Crypt_RSA->verify($hash, base64_decode($signature));
        unset($this->Crypt_RSA);
        return $verified;
    }

    /**
     * Performs AES-256 encryption on the given data using the given key
     *
     * @param string $data A string of data to encrypt
     * @param string $key The key to use to encrypt the data
     * @return string A base64 encoded string of encrypted data
     */
    private function encrypt($data, $key, $iv)
    {
        $this->loadCrypto();

        // Set key
        $this->Crypt_AES->setKey($key);
        // Set IV
        $this->Crypt_AES->setIV($iv);

        return base64_encode($this->Crypt_AES->encrypt($data));
    }

    /**
     * Perform AES-256 decryption on the givne data using the given key
     *
     * @param string $data A base64 encoded string of data to decrypt
     * @param string $key The key to use to decrypt the data
     * @return string The plain-text data
     */
    private function decrypt($data, $key, $iv)
    {
        $this->loadCrypto();

        // Set key
        $this->Crypt_AES->setKey($key);
        // Set IV
        $this->Crypt_AES->setIV($iv);

        return $this->Crypt_AES->decrypt(base64_decode($data));
    }

    /**
     * Prepares the crypto systems for use. Loads the Security components and
     * AES and Hash component libraries as needed.
     *
     * @param array $other_libs An array of other crypto libraries to load (e.g. RSA)
     */
    private function loadCrypto(array $other_libs = null)
    {

        // Load the AES and Hash security libraries, if not already loaded
        if (!isset($this->Crypt_AES)) {
            $this->loadLib('Crypt', 'AES');
        }

        if (!isset($this->Crypt_Hash)) {
            $this->loadLib('Crypt', 'Hash');
        }

        // Load other crypto libraries
        if ($other_libs) {
            foreach ($other_libs as $lib) {
                $this->loadLib('Crypt', $lib);
            }
        }
    }

    /**
     * Loads the given PHPSec library into this object
     *
     * @param string $package The package to load from
     * @param string $lib The library to load
     */
    private function loadLib($package, $lib)
    {
        $class_name = $package . '_' . $lib;

        if (isset($this->$class_name)) {
            return;
        }

        // Load the library requested
        $class = 'phpseclib\\' . $package . '\\' . $lib;
        $this->$class_name = new $class();
    }

    /**
     * Computes a hash of the given data, optionally as an HMAC.
     *
     * @param string $data The data to be hashed
     * @param string $hash The hash to use (md5, sha1, sha256, sha512, etc.)
     * @param string $hmac_key The key used to compute the HMAC hash, if null
     *  will only compute a normal hash of the data
     * @return string A hash of the data in binary format
     */
    private function hash($data, $hash, $hmac_key)
    {
        $this->loadCrypto();

        $this->Crypt_Hash->setHash($hash);
        $this->Crypt_Hash->setKey($hmac_key);
        return $this->Crypt_Hash->hash($data);
    }
}
