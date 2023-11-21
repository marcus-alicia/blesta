<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Square API.
 *
 * @package blesta
 * @subpackage blesta.components.modules.square
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SquareApi
{
    // Load traits
    use Container;

    /**
     * @var string The application ID
     */
    private $application_id;

    /**
     * @var string The personal access token
     */
    private $access_token;

    /**
     * @var string The store location ID
     */
    private $location_id;

    /**
     * Initializes the class.
     *
     * @param string $application_id The application ID
     * @param string $access_token The personal access token
     * @param string $location_id The store location ID
     */
    public function __construct($application_id, $access_token, $location_id)
    {
        $this->application_id = $application_id;
        $this->access_token = $access_token;
        $this->location_id = $location_id;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a request to the Square API.
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
            'Authorization: Bearer ' . $this->access_token
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

        // Build PUT request
        if ($type == 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POST, true);

            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }
        }

        // Build DELETE request
        if ($type == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // Execute request
        curl_setopt($ch, CURLOPT_URL, 'https://connect.squareup.com/v2/' . trim($method, '/'));

        $response = curl_exec($ch);

        if ($response == false) {
            $this->logger->error(curl_error($ch));
        }

        $data = json_decode($response);
        curl_close($ch);

        return $data;
    }

    /**
     * Build the payment request.
     *
     * @param string $client_email The client email address
     * @param string $line_items The order line items
     * @param string $address The client shipping address
     * @param string $transaction_id The order transaction id
     * @param string $redirect_url Successful redirect URL
     * @return stdClass An object containing the api response
     */
    public function buildPayment(
        $client_email,
        $line_items,
        $address = null,
        $transaction_id = null,
        $redirect_url = null
    ) {
        // Generate a unique ID
        $unique_id = uniqid();

        // Format line items
        foreach ($line_items as $key => $value) {
            // Force string format for quantity
            $value['quantity'] = (string) $value['quantity'];

            // Remove all formatting from the amount
            $value['base_price_money']['amount'] = (int) strtr(
                $value['base_price_money']['amount'],
                ['.' => '', ',' => '']
            );

            // Format line discounts
            if (isset($value['discounts'])) {
                foreach ($value['discounts'] as $discount_key => $discount_value) {
                    // Remove all formatting from the amount
                    $discount_value['amount_money']['amount'] = (int) strtr(
                        $discount_value['amount_money']['amount'],
                        ['.' => '', ',' => '']
                    );
                    $value['discounts'][$discount_key] = $discount_value;
                }
            }

            // Replace changes to the original array
            $line_items[$key] = $value;
        }

        // Build payment parameters array
        $idempotency_key = !empty($transaction_id) ? $transaction_id : $unique_id;
        $params = [
            'redirect_url' => $redirect_url,
            'idempotency_key' => $idempotency_key,
            'ask_for_shipping_address' => isset($address),
            'order' => [
                'idempotency_key' => $idempotency_key,
                'order' => [
                    'reference_id' => $idempotency_key,
                    'location_id' => $this->location_id,
                    'line_items' => $line_items
                ],
            ],
            'pre_populate_buyer_email' => $client_email
        ];

        // Add shipping address if is set
        if (isset($address)) {
            $params['pre_populate_shipping_address'] = [
                'address_line_1' => $address['address1'],
                'address_line_2' => $address['address2'],
                'locality' => $address['city'],
                'administrative_district_level_1' => $address['state']['name'],
                'postal_code' => $address['zip'],
                'country' => $address['country']['alpha2'],
                'first_name' => $address['first_name'],
                'last_name' => $address['last_name']
            ];
        }

        return $this->apiRequest('/locations/' . $this->location_id . '/checkouts', $params, 'POST');
    }

    /**
     * Retrieves details for a single transaction.
     *
     * @param string $transaction_id The transaction id
     * @return stdClass An object containing the transaction details
     */
    public function getTransaction($transaction_id)
    {
        return $this->apiRequest('/locations/' . $this->location_id . '/transactions/' . $transaction_id);
    }

    /**
     * Retrieves details for a order.
     *
     * @param string $order_id The order id
     * @return stdClass An object containing the order details
     */
    public function getOrder($order_id)
    {
        $params = [
            'order_ids' => [
                $order_id
            ]
        ];
        $response = $this->apiRequest('/locations/' . $this->location_id . '/orders/batch-retrieve', $params, 'POST');

        return isset($response->orders[0]) ? $response->orders[0] : $response;
    }
}
