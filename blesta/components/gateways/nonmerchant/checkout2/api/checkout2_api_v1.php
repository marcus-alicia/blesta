<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'checkout2_api.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'checkout2_response_v1.php';

/**
 * 2Checkout API V1
 *
 * The 2Checkout API documentation can be found at:
 * https://www.2checkout.com/documentation/api/
 * 2Checkout INS:
 * https://www.2checkout.com/static/va/documentation/INS/index.html
 *
 * Configure 2Checkout Account->Site Management Settings as follows:
 *      Demo Setting: Parameter
 *      Direct Return: Direct Return (Your URL)
 *      Approved URL: This will be overwritten by this gateway
 * Configure 2Checkout Account->User Management Settings as follows (for API access):
 *      Grant a user access to the API and "API Updating"
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2.api
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Checkout2ApiV1 extends Checkout2Api
{
    // Load traits
    use Container;

    /**
     * @var string The URL to post payments to
     */
    private $apiUrl = 'https://www.2checkout.com';
    /**
     * @var string The 2Checkout API username
     */
    private $apiUsername;
    /**
     * @var string The 2Checkout API password
     */
    private $apiPassword;

    /**
     * Initializes the request authentication parameters
     *
     * @param string $apiUsername The wallet ID
     * @param string $apiPassword The wallet token
     */
    public function __construct($apiUsername, $apiPassword, $sandbox = 'false')
    {
        $this->apiUsername = $apiUsername;
        $this->apiPassword = $apiPassword;

        if ($sandbox == 'true') {
            $this->apiUrl = 'https://sandbox.2checkout.com';
        }

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send an API request to 2Checkout
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return \Checkout2Response
     */
    private function apiRequest($route, array $body, $method)
    {
        $url = $this->apiUrl . '/' . $route;
        $curl = curl_init();

        switch (strtoupper($method)) {
            case 'DELETE':
                // Set data using get parameters
            case 'GET':
                $url .= empty($body) ? '' : '?' . http_build_query($body);
                break;
            case 'POST':
                curl_setopt($curl, CURLOPT_POST, 1);
                // Use the default behavior to set data fields
            default:
                // For whatever reason the API requires that the parameters be encoded as a http query string
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));
                break;
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERPWD, $this->apiUsername . ':' . $this->apiPassword);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $headers = [];
        $headers[] = 'Accept: application/json';
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $this->lastRequest = ['content' => $body, 'headers' => $headers];
        $result = curl_exec($curl);

        if (curl_errno($curl)) {
            $this->logger->error(curl_error($curl));

            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];

            return new Checkout2ResponseV1(['content' => json_encode($error), 'headers' => []]);
        }
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);

        // Return request response
        return new Checkout2ResponseV1(
            ['content' => substr($result, $headerSize), 'headers' => explode("\n", substr($result, 0, $headerSize))]
        );
    }

    /**
     * Fetches the url to redirect clients to for payment in 2Checkout
     *
     * @return string The payment url
     */
    public function getPaymentUrl()
    {
        return $this->apiUrl . '/checkout/purchase';
    }

    /**
     * Refunds a charge in 2Checkout
     *
     * @param array $params A list of parameters for issuing a refund
     * @return \Checkout2Response
     */
    public function refund(array $params)
    {
        return $this->apiRequest('api/sales/refund_invoice', $params, 'POST');
    }
}
