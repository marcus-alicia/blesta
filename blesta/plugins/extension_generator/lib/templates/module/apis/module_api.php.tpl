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
    # EDIT REQUIRED Update the below API url or replace it with an appropriate module row variable
    ##
    /**
     * @var string The API URL
     */
    private $apiUrl = '';{{array:module_rows}}

    /**
     * @var {{if:module_rows.type:Checkbox}}bool{{else:module_rows.type}}string{{endif:module_rows.type}} Placeholder description
     */
    private ${{module_rows.name}};{{array:module_rows}}
    ##
    # EDIT REQUIRED Update the above variable descriptions
    ##

    // The data sent with the last request served by this API
    private $lastRequest = [];

    /**
     * Initializes the request parameter
     *{{array:module_rows}}
     * @param string ${{module_rows.name}} Placeholder description{{array:module_rows}}
     */
    ##
    # EDIT REQUIRED Update the above variable descriptions and parameter list below
    ##
    public function __construct({{array:module_rows}}${{module_rows.name}},{{array:module_rows}})
    {{{array:module_rows}}
        $this->{{module_rows.name}} = ${{module_rows.name}};{{array:module_rows}}
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

//    The above apiRequest() method is publically accessible and can be used for any necessary requests
//    but it is often useful to define helper functions like the one below for convenience
//
//    /**
//     * Fetch customer info from {{name}}
//     *
//     * @param string $email The email by which to identify the customer and use for login
//     * @return {{class_name}}Response
//     */
//    public function getUser($email)
//    {
//        return $this->apiRequest('customer/list', ['customers' => [$email]], 'POST');
//    }
}
