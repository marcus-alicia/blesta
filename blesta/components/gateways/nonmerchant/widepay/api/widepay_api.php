<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'widepay_response.php';

/**
 * Wide Pay API
 *
 * @package blesta
 * @subpackage blesta.components.gateways.widepay.apis
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class WidepayApi
{
    // Load traits
    use Container;

    /**
     * @var string The API URL
     */
    private $apiUrl = 'https://api.widepay.com/v1';
    /**
     * @var string The Widepay wallet ID
     */
    private $walletId;
    /**
     * @var string The Widepay wallet token
     */
    private $walletToken;

    /**
     * Initializes the request parameter
     *
     * @param string $walletId The wallet ID
     * @param string $walletToken The wallet token
     */
    public function __construct($walletId, $walletToken)
    {
        $this->walletId = $walletId;
        $this->walletToken = $walletToken;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send an API request to WidePay
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return WidepayResponse
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
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));
                break;
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_USERPWD, $this->walletId . ':' . $this->walletToken);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        $headers = [];
        $headers[] = 'WP-API: SDK-PHP';
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

            return new WidepayResponse(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new WidepayResponse(['content' => $data[count($data) - 1], 'headers' => array_splice($data, 0, count($data) - 1)]);
    }

    /**
     * Creates a charge in Wide Pay
     *
     * @param array $params A list of parameters for creating a charge
     * @return WidepayResponse
     */
    public function createCharge(array $params)
    {
        return $this->apiRequest('recebimentos/cobrancas/adicionar', $params, 'POST');
    }

    /**
     * Gets an existing Wide Pay charge based on the notification ID
     *
     * @param string $notification_id The ID by which to fetch a charge
     * @return WidepayResponse
     */
    public function getNotificationCharge($notification_id)
    {
        return $this->apiRequest('recebimentos/cobrancas/notificacao', ['id' => $notification_id], 'POST');
    }

    /**
     * Gets an existing Wide Pay charge based on the charge ID
     *
     * @param string $charge_id The ID by which to fetch a charge
     * @return WidepayResponse
     */
    public function getCharge($charge_id)
    {
        return $this->apiRequest('recebimentos/cobrancas/consultar', ['id' => $charge_id], 'POST');
    }

    /**
     * Cancels a charge in Wide Pay
     *
     * @param string $charge_id The ID of the charge to cancel
     * @return WidepayResponse
     */
    public function cancelCharge($charge_id)
    {
        return $this->apiRequest('recebimentos/cobrancas/cancelar', ['id' => $charge_id], 'POST');
    }
}
