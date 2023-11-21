<?php

namespace CoinGate;

use Blesta\Core\Util\Common\Traits\Container;
use \Configure;

class CoinGate
{
    // Load traits
    use Container;

    const VERSION = '2.0.1';
    const USER_AGENT_ORIGIN = 'CoinGate PHP Library';

    public static $appID = '';
    public static $apiKey = '';
    public static $apiSecret = '';
    public static $environment = 'live';
    public static $userAgent = '';

    public static function config($authentication)
    {
        if (isset($authentication['app_id'])) {
            self::$appID = $authentication['app_id'];
        }

        if (isset($authentication['api_key'])) {
            self::$apiKey = $authentication['api_key'];
        }

        if (isset($authentication['api_secret'])) {
            self::$apiSecret = $authentication['api_secret'];
        }

        if (isset($authentication['environment'])) {
            self::$environment = $authentication['environment'];
        }

        if (isset($authentication['user_agent'])) {
            self::$userAgent = $authentication['user_agent'];
        }
    }

    public static function testConnection($authentication = [])
    {
        try {
            self::request('/auth/test', 'GET', [], $authentication);

            return true;
        } catch (\Exception $e) {
            return get_class($e) . ': ' . $e->getMessage();
        }
    }

    public static function request($url, $method = 'POST', $params = [], $authentication = [])
    {
        // Initialize logger
        $self_object = new self();
        $logger = $self_object->getFromContainer('logger');

        $appID = isset($authentication['app_id']) ? $authentication['app_id'] : self::$appID;
        $apiKey = isset($authentication['api_key']) ? $authentication['api_key'] : self::$apiKey;
        $apiSecret = isset($authentication['api_secret']) ? $authentication['api_secret'] : self::$apiSecret;
        $environment = isset($authentication['environment']) ? $authentication['environment'] : self::$environment;
        $userAgent = isset($authentication['user_agent']) ? $authentication['user_agent'] : (isset(self::$userAgent) ? self::$userAgent : (self::USER_AGENT_ORIGIN . ' v' . self::VERSION));

        # Check if credentials was passed
        if (empty($appID) || empty($apiKey) || empty($apiSecret)) {
            \CoinGate\Exception::throwException(400, ['reason' => 'CredentialsMissing']);
        }

        # Check if right environment passed
        $environments = ['live', 'sandbox'];

        if (!in_array($environment, $environments)) {
            $availableEnvironments = join(', ', $environments);
            \CoinGate\Exception::throwException(400, [
                    'reason' => 'BadEnvironment',
                    'message' => 'Environment does not exist. Available environments: ' . $availableEnvironments
                ]);
        }

        $url = ($environment === 'sandbox' ? 'https://api-sandbox.coingate.com/v1' : 'https://api.coingate.com/v1') . $url;
        $nonce = (int) (microtime(true) * 1e6);
        $message = $nonce . $appID . $apiKey;
        $signature = hash_hmac('sha256', $message, $apiSecret);
        $headers = [];
        $headers[] = 'Access-Key: ' . $apiKey;
        $headers[] = 'Access-Nonce: ' . $nonce;
        $headers[] = 'Access-Signature: ' . $signature;
        $curl = curl_init();

        $curlOptions = [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url
        ];

        if ($method == 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            array_merge($curlOptions, [CURLOPT_POST => 1]);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        curl_setopt_array($curl, $curlOptions);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        $httpStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($response == false) {
            $logger->error(curl_error($curl));
        }

        if ($httpStatus === 200) {
            return $response;
        } else {
            \CoinGate\Exception::throwException($httpStatus, $response);
        }
    }
}
