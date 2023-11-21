<?php
use Blesta\Core\Util\Common\Traits\Container;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cwatch_response.php';

class CwatchApi
{
    // Load traits
    use Container;

    // API endpoint URL
    private $apiUrl;

    // API Token for request authentication
    private $token = '';

    // The data sent with the last request served by this API
    private $lastRequest = [];

    /**
     * CwatchApi constructor.
     *
     * @param string $username The username of the API user
     * @param string $password The password of the API user
     * @param bool $sandbox True to use the sandbox API endpoint, false otherwise
     */
    public function __construct($username, $password, $sandbox)
    {
        if ($sandbox) {
            $this->apiUrl = 'http://cwatchpartnerportalstaging-env.us-east-1.elasticbeanstalk.com';
        } else {
            $this->apiUrl = 'https://partner.cwatch.comodo.com';
        }

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;

        $response = $this->apiRequest('login', ['username' => $username, 'password' => $password], 'POST');
        if ($response) {
            $this->setToken($response->headers());
        }
    }

    /**
     * Sets the API authorization token for all requests run through this API
     *
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Create a customer account in cWatch
     *
     * @param string $email The email by which to identify the customer and use for login
     * @param string $firstName The customer's first name
     * @param string $lastName The customer's last name
     * @param string $country The 3-character country code of the customer
     * @return CwatchResponse
     */
    public function addUser($email, $firstName, $lastName, $country)
    {
        $params = ['email' => $email, 'name' => $firstName, 'surname' => $lastName, 'country' => $country];

        return $this->apiRequest('customer/add', $params, 'POST');
    }

    /**
     * Edit a customer account in cWatch
     *
     * @param int $customer_id The cWatch ID of the customer being modified
     * @param string $firstName The customer's first name
     * @param string $lastName The customer's last name
     * @param string $country The 3-character country code of the customer
     * @return CwatchResponse
     */
    public function editUser($customer_id, $firstName, $lastName, $country)
    {
        $params = ['name' => $firstName, 'surname' => $lastName, 'country' => $country];

        return $this->apiRequest('customer/update/' . $customer_id, $params, 'PUT');
    }

    /**
     * Delete the given user
     *
     * @param string $email The email of the customer to delete
     * @return CwatchResponse
     */
    public function deleteUser($email)
    {
        return $this->apiRequest('customer/deleteCustomer', ['email' => $email], 'DELETE');
    }

    /**
     * Fetch customer info from cWatch
     *
     * @param string $email The email by which to identify the customer and use for login
     * @return CwatchResponse
     */
    public function getUser($email)
    {
        return $this->apiRequest('customer/list', ['customers' => [$email]], 'POST');
    }

    /**
     * Fetch customer info from cWatch
     *
     * @param int $camID The cWatch customer cam ID
     * @param string $email The email by which to identify the customer and use for login
     * @return CwatchResponse
     */
    public function getLogin($camID, $email)
    {
        return $this->apiRequest('admin/loginAsUrl', ['customerCamId' => $camID, 'customerEmail' => $email], 'GET');
    }

    /**
     * Create a license of the given type for the given user
     *
     * @param string $licenseType The type of license to create ("STARTER", "BASIC_DETECTION", "PRO", "PRO_FREE",
     *  "PRO_FREE_60D", "PREMIUM", "PREMIUM_FREE", "PREMIUM_FREE_60D")
     * @param string $term The term for which the license will remain valid before renewal ("MONTH_1", "MONTH_12",
     *  "MONTH_24", "MONTH_36", "MONTH_2", "UNLIMITED")
     * @param string $email The email by which to identify the customer
     * @param string $firstName The customer's first name
     * @param string $lastName The customer's last name
     * @param string $country The 3-character country code of the customer
     * @return CwatchResponse
     */
    public function addLicense($licenseType, $term, $email, $firstName, $lastName, $country)
    {
        $params = [
            'term' => $term,
            'product' => $licenseType,
            'customers' => [['email' => $email, 'name' => $firstName, 'surname' => $lastName, 'country' => $country]],
            'autoLicenseUpgrade' => false,
            'renewAutomatically' => true
        ];

        return $this->apiRequest('customer/distributeLicenseForCustomers', $params, 'POST');
    }

    /**
     * Changes the pricing level of the given license
     *
     * @param string $licenseType The type of license to upgrade to ("STARTER", "PRO", "PREMIUM")
     * @param string $customerId The ID of the customer in cWatch
     * @param string $licenseKey The key of the license to change pricing for
     * @return CwatchResponse
     */
    public function changeLicensePricing($licenseType, $customerId, $licenseKey)
    {
        $params = [
            'product' => $licenseType,
            'customerId' => $customerId,
            'licenseKey' => $licenseKey
        ];

        return $this->apiRequest('customer/changeLicensePricing', $params, 'POST');
    }

    /**
     * Deactivates the given license
     *
     * @param string $licenseKey The key of the license to disable
     * @return CwatchResponse
     */
    public function deactivateLicense($licenseKey)
    {
        return $this->apiRequest('customer/deactivatelicense', ['licenses' => [$licenseKey]], 'PUT');
    }

    /**
     * Provision a site for a license in Cwatch
     *
     * @param array $params
     *     - email The email of the customer to add this site for
     *     - domain The domain to be provisioned
     *     - licenseKey The key for the license to associate with this site
     *     - initiateDns Whether to start scaning DNS records
     *     - autoSsl Whether to install an ssl certificate
     * @return CwatchResponse
     */
    public function addSite(array $params)
    {
        return $this->apiRequest('siteprovision/add', [$params], 'POST');
    }

    /**
     * Provision a site for a license in Cwatch
     *
     * @param array $params
     *     - renew
     *     - email The email of the customer to change this site for
     *     - site The domain of the site to be changed
     *     - licenseKeyNew The key for the new license to associate with this site
     * @return CwatchResponse
     */
    public function upgradeLicenseForSite(array $params)
    {
        return $this->apiRequest('customer/upgradeLicenseForSite', $params, 'POST');
    }

    /**
     * Remove a site from Cwatch
     *
     * @param string $email The email of the customer to remove this site for
     * @param string $domain The domain to be removed
     * @return CwatchResponse
     */
    public function removeSite($email, $domain)
    {
        $params = ['domain' => $domain, 'engineSiteId' => ''];
        $siteResponse = $this->getSite($email, $domain);
        $siteErrors = $siteResponse->errors();
        if (empty($siteErrors)) {
            $site = $siteResponse->response();
            $params['engineSiteId'] = $site->engineSiteId;
        }

        return $this->apiRequest('admin/removeDomain', $params, 'POST');
    }

    /**
     * Get sites by customer email
     *
     * @param string $email The customer's email
     * @return CwatchResponse
     */
    public function getSites($email)
    {
        return $this->apiRequest('customer/site/listByEmail', ['email' => $email], 'GET');
    }

    /**
     * Checks the existence of a domain in cWatch
     *
     * @param string $domain The domain of the site to check
     * @return CwatchResponse
     */
    public function checkSite($domain)
    {
        return $this->apiRequest(
            'customer/site/check/' . urlencode($domain),
            [],
            'GET'
        );
    }

    /**
     * Get sites by customer email and domain
     *
     * @param string $email The customer's email
     * @param string $domain The domain of the site to fetch
     * @return CwatchResponse
     */
    public function getSite($email, $domain)
    {
        return $this->apiRequest(
            'customer/site/listBySite',
            ['email' => $email, 'siteName' => $domain],
            'GET'
        );
    }

    /**
     * Get sites provision requests by customer email
     *
     * @param string $email The customer's email
     * @return CwatchResponse
     */
    public function getSiteProvisions($email)
    {
        return $this->apiRequest(
            'siteprovision/item/getByCustomer',
            ['customerEmail' => $email],
            'GET'
        );
    }

    /**
     * Fetch license info for the admin
     *
     * @return CwatchResponse
     */
    public function getAdmin()
    {
        return $this->apiRequest('admin/summary', [], 'GET');
    }

    /**
     * Fetch license info for a given license key
     *
     * @param string $licenseKey The key of the license
     * @return CwatchResponse
     */
    public function getLicense($licenseKey)
    {
        return $this->apiRequest('customer/showLicenceByKey', ['licenseKey' => $licenseKey], 'GET');
    }

    /**
     * Fetch licenses for the given customer
     *
     * @param string $email The customer email to fetch licenses for
     * @return CwatchResponse
     */
    public function getLicenses($email, $active_only = true)
    {
        return $this->apiRequest(
            'customer/listlicencebyemail',
            ['email' => $email, 'activeLicenseOnly' => ($active_only ? 'true' : 'false')],
            'GET'
        );
    }

    /**
     * Fetch discount information for upgrading a license
     *
     * @param string $email The customer email to fetch license info for
     * @param string $licenseKey The key of the license
     * @return CwatchResponse
     */
    public function getDiscountInfo($email, $licenseKey)
    {
        return $this->apiRequest(
            'customer/getDiscountInfo',
            ['email' => $email, 'licenseKey' => $licenseKey],
            'GET'
        );
    }

    /**
     * Check the malware scanner status for a given damin
     *
     * @param string $email The customer to check the domain for
     * @param string $domain The domain to check
     * @return CwatchResponse
     */
    public function getScanner($email, $domain)
    {
        $domainId = '';
        $siteResponse = $this->getSite($email, $domain);
        $siteErrors = $siteResponse->errors();
        if (empty($siteErrors)) {
            $site = $siteResponse->response();
            $domainId = $site->engineSiteId;
        }

        return $this->apiRequest('/domain/' . $domainId . '/settings/scanner', [], 'GET');
    }

    /**
     * Check the malware scanner status for a given damin
     *
     * @param string $email The customer to check the domain for
     * @param string $domain The domain to check
     * @return CwatchResponse
     */
    public function getMalware($email, $domain)
    {
        $domainId = '';
        $siteResponse = $this->getSite($email, $domain);
        $siteErrors = $siteResponse->errors();
        if (empty($siteErrors)) {
            $site = $siteResponse->response();
            $domainId = $site->engineSiteId;
        }

        return $this->apiRequest(
            '/domain/' . $domainId . '/malwareremoval',
            ['pageSize' => '25', 'pageNumber' => '1'],
            'GET'
        );
    }

    /**
     * Check a malware scanner for a given damin
     *
     * @param string $email The customer to add the scanner for
     * @param array $params
     *     - domain The domain to scan
     *     - login The username for FTP access
     *     - password The password for FTP access
     *     - host The host to use for FTP access
     *     - port The port to use for FTP access
     *     - path The path to the web directory for this site
     * @return CwatchResponse
     */
    public function addScanner($email, array $params)
    {
        $domainId = '';
        $siteResponse = $this->getSite($email, $params['domain']);
        $siteErrors = $siteResponse->errors();
        if (empty($siteErrors)) {
            $site = $siteResponse->response();
            $domainId = $site->engineSiteId;
        }

        return $this->apiRequest('/domain/' . $domainId . '/settings/scanner/ftp', $params, 'POST');
    }

    /**
     * Gets a list of available license types
     */
    public function getLicenseTypes()
    {
        return $this->apiRequest('/partner/getDistributableLicenseTypes', [], 'GET');
    }

    /**
     * Send an API request to cWatch server
     *
     * @param string $route The path to the API method
     * @param array $body The data to be sent
     * @param string $method Data transfer method (POST, GET, PUT, DELETE)
     * @return array
     */
    private function apiRequest($route, array $body, $method)
    {
        $url = $this->apiUrl . '/' . $route;
        $ch = curl_init();

        switch (strtoupper($method)) {
            case 'GET':
            case 'DELETE':
                $url .= empty($body) ? '' : '?' . http_build_query($body);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, 1);
            default:
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                break;
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $headers = [];
        $headers[] = 'Authorization: ' . $this->token;
        $headers[] = 'Cache-Control: no-cache';
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $this->lastRequest = ['content' => $body, 'headers' => $headers];
        $result = curl_exec($ch);

        if ($result == false) {
            $this->logger->error(curl_error($ch));
        }

        if (curl_errno($ch)) {
            $error = [
                'error' => 'Curl Error',
                'message' => 'An internal error occurred, or the server did not respond to the request.',
                'status' => 500
            ];

            return new CwatchResponse(['content' => json_encode($error)]);
        }

        curl_close($ch);

        $authorization = '';
        $data = explode("\n", $result);
        foreach ($data as $part) {
            $splitPart = explode(':', $part);
            if ($splitPart[0] == 'Authorization' && isset($splitPart[1])) {
                $authorization = $splitPart[1];
                break;
            }
        }

        // Return request response
        return new CwatchResponse(['content' => $data[count($data) - 1], 'headers' => $authorization]);
    }

    /**
     * Returns the data sent in the last API request
     *
     * @return array The data sent in the last API request
     */
    public function lastRequest()
    {
        return $this->lastRequest;
    }
}
