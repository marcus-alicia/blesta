<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'checkout2_api.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'checkout2_response_v5.php';

/**
 * 2Checkout API V5
 *
 * The 2Checkout REST 5.0 API documentation can be found at:
 * https://knowledgecenter.2checkout.com/Integration/REST_5.0_Reference
 * Buylink Parameters can be found at:
 * https://knowledgecenter.2checkout.com/Documentation/07Commerce/2Checkout_ConvertPlus/ConvertPlus_URL_parameters
 * Test Order instructions can be found at:
 * https://knowledgecenter.2checkout.com/Documentation/09Test_ordering_system
 *
 * 2Checkout > Integrations > Webhooks & API > IPN settings:
 *      Response tags: Select 'IPN_EXTERNAL_REFERENCE' and 'EXTERNAL_CUSTOMER_REFERENCE'
 *      IPN URLs: Add a URL that 2checkout will send formatted order info to when the order status is changed
 *
 * @package blesta
 * @subpackage blesta.components.gateways.checkout2.api
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Checkout2ApiV5 extends Checkout2Api
{
    // Load traits
    use Container;

    /**
     * @var string The URL to post payments to
     */
    private $apiUrl = 'https://api.2checkout.com';
    /**
     * @var string The 2Checkout merchant code
     */
    private $merchantCode;
    /**
     * @var string The 2Checkout API secret key
     */
    private $secretKey;

    /**
     * Initializes the request authentication parameters
     *
     * @param string $merchantCode The merchant code
     * @param string $secretKey The secret key
     */
    public function __construct($merchantCode, $secretKey)
    {
        $this->merchantCode = $merchantCode;
        $this->secretKey = $secretKey;

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
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
                break;
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $time = gmdate('Y-m-d H:i:s');
        $headers = [];
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: application/json';
        $hashString = strlen($this->merchantCode) . $this->merchantCode . strlen($time) . $time;
        $headers[] = 'X-Avangate-Authentication: code="' . $this->merchantCode . '"'
            . ' date="' . $time . '" hash="' . hash_hmac('md5', $hashString, $this->secretKey) . '"';
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

            return new Checkout2ResponseV5(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new Checkout2ResponseV5(
            ['content' => $data[count($data) - 1], 'headers' => array_splice($data, 0, count($data) - 1)]
        );
    }

    /**
     * Fetches the url to redirect clients to for payment in 2Checkout
     *
     * @return string The payment url
     */
    public function getPaymentUrl()
    {
        return 'https://secure.2checkout.com/checkout/buy';
    }

    /**
     * Refunds a charge in 2Checkout
     *
     * @param array $params A list of parameters for issuing a refund
     * @return \Checkout2Response
     */
    public function refund(array $params)
    {
        $referenceNumber = isset($params['refno']) ? $params['refno'] : '';
        unset($params['refno']);
        return $this->apiRequest('rest/5.0/orders/' . $referenceNumber . '/refund/', $params, 'POST');
    }
}
