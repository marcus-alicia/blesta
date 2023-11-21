<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '{{snake_case_name}}_response.php';

/**
 * {{name}} API
 *{{array:authors}}
 * @link {{authors.url}} {{authors.name}}{{array:authors}}
 */
class {{class_name}}Api
{
    ##
    # EDIT REQUIRED Update the below API url or replace it with an appropriate gateway field
    ##
    /**
     * @var string The API URL
     */
    private $apiUrl = '';{{array:fields}}

    /**
     * @var {{if:fields.type:Checkbox}}bool{{else:fields.type}}string{{endif:fields.type}} Placeholder description
     */
    private ${{fields.name}};{{array:fields}}
    ##
    # EDIT REQUIRED Update the above variable descriptions
    ##

    // The data sent with the last request served by this API
    private $lastRequest = [];

    /**
     * Initializes the request parameter
     *{{array:fields}}
     * @param string ${{fields.name}} Placeholder description{{array:fields}}
     */
    ##
    # EDIT REQUIRED Update the above variable descriptions and parameter list below
    ##
    public function __construct({{array:fields}}${{fields.name}},{{array:fields}})
    {{{array:fields}}
        $this->{{fields.name}} = ${{fields.name}};{{array:fields}}
    }

    /**
     * Send an API request to {{class_name}}
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return {{class_name}}Response
     */
    public function apiRequest($route, array $body, $method)
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
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);

        $headers = [];
        ##
        #  Set any neccessary headers here
        ##
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $this->lastRequest = ['content' => $body, 'headers' => $headers];
        $result = curl_exec($curl);
        if (curl_errno($curl)) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];

            return new {{class_name}}Response(['content' => json_encode($error), 'headers' => []]);
        }
        curl_close($curl);

        $data = explode("\n", $result);

        // Return request response
        return new {{class_name}}Response([
            'content' => $data[count($data) - 1],
            'headers' => array_splice($data, 0, count($data) - 1)]
        );
    }

////    The above apiRequest() method is publically accessible and can be used for any necessary requests
////    but it is often useful to define helper functions like the ones below for convenience

{{function:storeCc}}
    /**
     * Store a credit card
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function storeCc(array $vars)
    {
        return $this->apiRequest('card/add', $params, 'POST');
    }{{function:storeCc}}{{function:updateCc}}

    /**
     * Update a credit card stored off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function updateCc(array $vars)
    {
        return $this->apiRequest('card/update', $params, 'POST');
    }{{function:updateCc}}{{function:removeCc}}

    /**
     * Remove a credit card stored off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function removeCc(array $vars)
    {
        return $this->apiRequest('card/remove', $params, 'POST');
    }{{function:removeCc}}{{function:processStoredCc}}

    /**
     * Charge a credit card stored off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function processStoredCc(array $vars)
    {
        return $this->apiRequest('card/process', $params, 'POST');
    }{{function:processStoredCc}}{{function:authorizeStoredCc}}

    /**
     * Authorizees a credit card stored off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function authorizeStoredCc(array $vars)
    {
        return $this->apiRequest('card/authorize', $params, 'POST');
    }{{function:authorizeStoredCc}}{{function:captureStoredCc}}

    /**
     * Charge a previously authorized credit card stored off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function captureStoredCc(array $vars)
    {
        return $this->apiRequest('card/capture', $params, 'POST');
    }{{function:captureStoredCc}}{{function:voidStoredCc}}

    /**
     * Void an off site credit card charge
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function voidStoredCc(array $vars)
    {
        return $this->apiRequest('card/void', $params, 'POST');
    }{{function:voidStoredCc}}{{function:refundStoredCc}}

    /**
     * Refund an off site credit card charge
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function refundStoredCc(array $vars)
    {
        return $this->apiRequest('card/refund', $params, 'POST');
    }{{function:refundStoredCc}}{{function:processCc}}

    /**
     * Charge a credit card
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function processCc(array $vars)
    {
        return $this->apiRequest('cctransaction/add', $params, 'POST');
    }{{function:processCc}}{{function:authorizeCc}}

    /**
     * Authorize a credit card
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function authorizeCc(array $vars)
    {
        return $this->apiRequest('cctransaction/authorize', $params, 'POST');
    }{{function:authorizeCc}}{{function:captureCc}}

    /**
     * Capture the funds of a previously authorized credit card
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function captureCc(array $vars)
    {
        return $this->apiRequest('cctransaction/capture', $params, 'POST');
    }{{function:captureCc}}{{function:voidCc}}

    /**
     * Void a credit card charge
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function voidCc(array $vars)
    {
        return $this->apiRequest('cctransaction/void', $params, 'POST');
    }{{function:voidCc}}{{function:refundCc}}

    /**
     * Refund a credit card charge
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function refundCc(array $vars)
    {
        return $this->apiRequest('cctransaction/refund', $params, 'POST');
    }{{function:refundCc}}{{function:storeAch}}

    /**
     * Store an ACH account off site
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function storeAch(array $vars)
    {
        return $this->apiRequest('ach/add', $params, 'POST');
    }{{function:storeAch}}{{function:updateAch}}

    /**
     * Update an off site ACH account
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function updateAch(array $vars)
    {
        return $this->apiRequest('ach/update', $params, 'POST');
    }{{function:updateAch}}{{function:removeAch}}

    /**
     * Remove an off site ACH account
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function removeAch(array $vars)
    {
        return $this->apiRequest('ach/remove', $params, 'POST');
    }{{function:removeAch}}{{function:processStoredAch}}

    /**
     * Process an off site ACH account transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function processStoredAch(array $vars)
    {
        return $this->apiRequest('ach/process', $params, 'POST');
    }{{function:processStoredAch}}{{function:voidStoredAch}}

    /**
     * Void an off site ACH account transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function voidStoredAch(array $vars)
    {
        return $this->apiRequest('ach/void', $params, 'POST');
    }{{function:voidStoredAch}}{{function:refundStoredAch}}

    /**
     * Refund an off site ACH account transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function refundStoredAch(array $vars)
    {
        return $this->apiRequest('ach/refund', $params, 'POST');
    }{{function:refundStoredAch}}{{function:processAch}}

    /**
     * Process an ACH transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function processAch(array $vars)
    {
        return $this->apiRequest('achtransaction/add', $params, 'POST');
    }{{function:processAch}}{{function:voidAch}}

    /**
     * Void an ACH transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function voidAch(array $vars)
    {
        return $this->apiRequest('achtransaction/void', $params, 'POST');
    }{{function:voidAch}}{{function:refundAch}}

    /**
     * Refund an ACH transaction
     *
     * @param array $vars An array of card info to store off site including:
////     *
////     *  - variable_name A list of variables should be documented here but they
////     *      varry from gateway to gateway so you'll need to add them manually
     * @return {{class_name}}Response The api response object
     */
    public function refundAch(array $vars)
    {
        return $this->apiRequest('achtransaction/refund', $params, 'POST');
    }{{function:refundAch}}
}
