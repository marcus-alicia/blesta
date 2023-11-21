<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 *
 * A PHP class that acts as wrapper for the Elavon Converge API
 *
 * @author Mark Roland
 * @copyright 2014 Mark Roland
 * @license http://opensource.org/licenses/MIT
 * @link http://github.com/markroland/converge-api-php
 *
 * */
class ConvergeApi
{
    // Load traits
    use Container;

    /**
     * Merchant ID
     * @var string
     */
    private $merchant_id = '';

    /**
     * User ID
     * @var string
     */
    private $user_id = '';

    /**
     * Pin
     * @var string
     */
    private $pin = '';

    /**
     * API Live mode
     * @var bool
     */
    private $live = true;

    /**
     * A variable to hold debugging information
     * @var array
     */
    public $debug = [];

    /**
     * Class constructor
     *
     * @param string $merchant_id Merchant ID
     * @param string $user_id User ID
     * @param string $pin PIN
     * @param bool $live True to use the Live server, false to use the Demo server
     * @return null
     * */
    public function __construct($merchant_id, $user_id, $pin, $live = true)
    {
        $this->merchant_id = $merchant_id;
        $this->user_id = $user_id;
        $this->pin = $pin;
        $this->live = $live;

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * Send a HTTP request to the API
     *
     * @param string $api_method The API method to be called
     * @param string $http_method The HTTP method to be used (GET, POST, PUT, DELETE, etc.)
     * @param array $data Any data to be sent to the API
     * @return string
     * */
    private function sendRequest($api_method, $http_method = 'GET', $data = null)
    {
        // Standard data
        $data['ssl_merchant_id'] = $this->merchant_id;
        $data['ssl_user_id'] = $this->user_id;
        $data['ssl_pin'] = $this->pin;
        $data['ssl_show_form'] = 'false';
        $data['ssl_result_format'] = 'ascii';
        $data['ssl_test_mode'] = 'false';

        // Set request
        if ($this->live) {
            $request_url = 'https://www.myvirtualmerchant.com/VirtualMerchant/process.do';
        } else {
            $request_url = 'https://demo.myvirtualmerchant.com/VirtualMerchantDemo/process.do';
        }

        // Debugging output
        $this->debug = [];
        $this->debug['HTTP Method'] = $http_method;
        $this->debug['Request URL'] = $request_url;

        // Create a cURL handle
        $ch = curl_init();
        // Set the request
        curl_setopt($ch, CURLOPT_URL, $request_url);
        // Save the response to a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Set HTTP method
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $http_method);

        // This may be necessary, depending on your server's configuration
        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Send data
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            // Debugging output
            $this->debug['Posted Data'] = $data;
        }

        // Execute cURL request
        $curl_response = curl_exec($ch);

        if ($curl_response == false) {
            $this->logger->error(curl_error($ch));
        }

        // Save CURL debugging info
        $this->debug['Last Response'] = $curl_response;
        $this->debug['Curl Info'] = curl_getinfo($ch);

        // Close cURL handle
        curl_close($ch);

        // Parse response
        $response = $this->parseAsciiResponse($curl_response);

        // Return parsed response
        return $response;
    }

    /**
     * Parse an ASCII response
     * @param string $ascii_string An ASCII (plaintext) Response
     * @return array
     * */
    private function parseAsciiResponse($ascii_string)
    {
        $data = [];
        $lines = explode("\n", $ascii_string);
        if (count($lines)) {
            foreach ($lines as $line) {
                $kvp = explode('=', $line);
                $data[$kvp[0]] = $kvp[1];
            }
        }
        return $data;
    }

    /**
     * Credit Card Sale/Process Request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function ccsale(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'ccsale';
        return $this->sendRequest('ccsale', 'POST', $parameters);
    }

    /**
     * Credit Card Authorization Request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function ccauthonly(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'ccauthonly';
        return $this->sendRequest('ccauthonly', 'POST', $parameters);
    }

    /**
     * Credit Card Refund/Return Request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function ccreturn(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'ccreturn';
        return $this->sendRequest('ccreturn', 'POST', $parameters);
    }

    /**
     * Credit Card Void request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function ccvoid(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'ccvoid';
        return $this->sendRequest('ccvoid', 'POST', $parameters);
    }

    /**
     * Credi Card Complete/Capture request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function cccomplete(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'cccomplete';
        return $this->sendRequest('cccomplete', 'POST', $parameters);
    }

    /**
     * Submit "ccaddinstall" request
     * @param array $parameters Input parameters
     * @return array Response from Converge
     * */
    public function ccaddinstall(array $parameters = [])
    {
        $parameters['ssl_transaction_type'] = 'ccaddinstall';
        return $this->sendRequest('ccaddinstall', 'POST', $parameters);
    }
}

//
//$obj=new ConvergeApi("006546","webpage","JMC6FF",false);
//
//$sale=$obj->ccsale(
//		array(
//				 'ssl_amount'=>'9.99',
//				 'ssl_card_number'=>'5000300020003003',
//				 'ssl_cvv2cvc2'=>'123',
//				 'ssl_exp_date'=>'1222',
//				 'ssl_avs_zip'=>'37013',
//				 'ssl_avs_address'=>'123 main',
//				 'ssl_first_name'=>'Mr.',
//				 'ssl_last_name'=>'Smith'
//		)
//);
//echo '<pre>';
//print_r($sale);
