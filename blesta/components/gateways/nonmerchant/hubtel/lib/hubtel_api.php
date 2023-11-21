<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Hubtel API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.hubtel
 * @copyright Copyright (c) 2017, Phillips Data, Inc.
 * @license https://www.blesta.com/license/ The Blesta License Agreement
 * @link https://www.blesta.com/ Blesta
 */
class HubtelApi
{
    // Load traits
    use Container;

    /**
     * @var string The client ID
     */
    private $client_id;

    /**
     * @var string The client secret token
     */
    private $client_secret;

    /**
     * Initializes the class.
     *
     * @param string $client_id The client ID
     * @param string $client_secret The client secret token
     */
    public function __construct($client_id, $client_secret)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the Hubtel API.
     *
     * @param string $method Specifies the endpoint and method to invoke
     * @param array $params The parameters to include in the api call
     * @param string $type The HTTP request type
     * @return stdClass An object containing the api response
     */
    private function apiRequest($method, array $params = [], $type = 'GET')
    {
        // Send request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Set authentication details
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->client_id . ':' . $this->client_secret)
        ]);

        // Build GET request
        if ($type == 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        // Build POST request
        if ($type == 'POST') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        // Execute request
        curl_setopt($ch, CURLOPT_URL, 'https://api.hubtel.com/v1/merchantaccount/' . trim($method, '/'));

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        $data = json_decode($response);
        curl_close($ch);

        return $data;
    }

    /**
     * Creates an invoice.
     *
     * @param array $items A multi-dimensional numerical array containing
     *  the invoice items with the following arguments:
     *  - name: The name of the item or product.
     *  - quantity: The quantity of the item.
     *  - unit_price: The price of the item or product.
     *  - description: The description of the item. (Optional)
     * @param string $description The description of the invoice
     * @param array $store An array containing the store data with the following arguments:
     *  - name: The name of the company or store.
     *  - postal_address: The postal address of the store. (Optional)
     *  - phone: The phone number of the store. (Optional)
     * @param string $return_url The return url, the client will be redirected to this url
     * @param array $custom_data An array containing the custom data
     * @return stdClass An object containing the api response
     */
    public function createInvoice($items, $description, $store, $return_url, $custom_data = null)
    {
        // Calculate total amount of the items
        $total_amount = 0;
        foreach ($items as $key => $item) {
            $item['total_price'] = $item['quantity'] * (float) $item['unit_price'];
            $total_amount = $item['total_price'] + $total_amount;
            $items[$key] = $item;
        }

        // Build parameters array
        $params = [
            'invoice' => [
                'items' => $items,
                'total_amount' => $total_amount,
                'description' => $description
            ],
            'store' => $store,
            'custom_data' => $custom_data,
            'actions' => [
                'cancel_url' => $return_url,
                'return_url' => $return_url
            ]
        ];

        return $this->apiRequest('/onlinecheckout/invoice/create', $params, 'POST');
    }

    /**
     * Gets an invoice.
     *
     * @param string $token The token of the invoice
     * @return stdClass An object containing the api response
     */
    public function getInvoice($token)
    {
        return $this->apiRequest('/onlinecheckout/invoice/status/' . trim($token));
    }
}
