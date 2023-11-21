<?php
use Blesta\Core\Util\Common\Traits\Container;

/**
 * Use any way you want. Free for all
 *
 * @author a.andrijenko@gogetssl.com
 * @version 1.0
 **/
class GoGetSSLApi
{
    // Load traits
    use Container;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $lastStatus;

    /**
     * @var string
     */
    protected $lastResponse;

    /**
     * GoGetSSLApi constructor.
     *
     * @param false $sandbox
     * @param null $key
     */
    public function __construct($sandbox = false, $key = null)
    {
        if ($key) {
            $this->key = $key;
        }

        if ($sandbox) {
            $this->URL = 'https://sandbox.gogetssl.com/api';
        } else {
            $this->URL = 'https://my.gogetssl.com/api';
        }

        // Initialize logger
        $logger = $this->getFromContainer('logger');
        $this->logger = $logger;
    }

    /**
     * @param $user
     * @param $pass
     * @return false|mixed
     */
    public function auth($user, $pass)
    {
        $response = $this->call('/auth/', [], ['user' => $user, 'pass' => $pass]);

        if (!empty($response['key'])) {
            $this->key = $response['key'];
            return $response;
        }

        return false;
    }

    /**
     * @param $key
     */
    public function setKey($key)
    {
        if ($key) {
            $this->key = $key;
        }
    }

    /**
     * Decode CSR
     */
    public function decodeCSR($csr, $brand = 1, $wildcard = 0)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        if ($csr) {
            $postData['csr'] = $csr;
            $postData['brand'] = $brand;
            $postData['wildcard'] = $wildcard;
        }

        return $this->call('/tools/csr/decode/', $getData, $postData);
    }

    /**
     * Get Domain Emails List
     */
    public function getWebServers($type)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/tools/webservers/' . (int) $type, $getData);
    }

    /**
     * Get Domain Emails List
     */
    public function getDomainEmails($domain)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        if ($domain) {
            $postData['domain'] = $domain;
        }

        return $this->call('/tools/domain/emails/', $getData, $postData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getAllProductPrices()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/products/all_prices/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getAllProducts()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/products/', $getData);
    }

    /**
     * @param $productId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getProductDetails($productId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/products/details/' . $productId, $getData);
    }

    /**
     * @param $productId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getProductPrice($productId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/products/price/' . $productId, $getData);
    }

    /**
     * @param $productId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getUserAgreement($productId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/products/agreement/' . $productId, $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getAccountBalance()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/balance/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getAccountDetails()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getTotalOrders()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/total_orders/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getAllInvoices()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/invoices/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getUnpaidInvoices()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/invoices/unpaid/', $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getTotalTransactions()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/account/total_transactions/', $getData);
    }

    /**
     * @param $data
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function addSSLOrder($data)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/add_ssl_order/', $getData, $data);
    }

    /**
     * @param $data
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function addSSLRenewOrder($data)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/add_ssl_renew_order/', $getData, $data);
    }

    /**
     * @param $orderId
     * @param $data
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function reIssueOrder($orderId, $data)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/ssl/reissue/' . (int) $orderId, $getData, $data);
    }

    /**
     * @param $orderId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function activateSSLOrder($orderId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/ssl/activate/' . (int) $orderId, $getData);
    }

    /**
     * @param $orderId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getOrderStatus($orderId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/status/' . (int) $orderId, $getData);
    }

    /**
     * @param $orderId
     * @param $data
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function comodoClaimFreeEV($orderId, $data)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/ssl/comodo_claim_free_ev/' . (int) $orderId, $getData, $data);
    }

    /**
     * @param $orderId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getOrderInvoice($orderId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/invoice/' . (int) $orderId, $getData);
    }

    /**
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function getUnpaidOrders()
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/list/unpaid/', $getData);
    }

    /**
     * @param $orderId
     * @return mixed|string
     * @throws GoGetSSLAuthException
     */
    public function resendEmail($orderId)
    {
        if (!$this->key) {
            throw new GoGetSSLAuthException();
        } else {
            $getData = ['auth_key' => $this->key];
        }

        return $this->call('/orders/resendemail/' . (int) $orderId, $getData);
    }

    /**
     * @param $uri
     * @param array $getData
     * @param array $postData
     * @param false $forcePost
     * @param false $isFile
     * @return mixed|string
     */
    protected function call($uri, $getData = [], $postData = [], $forcePost = false, $isFile = false)
    {
        $url = $this->URL . $uri;
        if (!empty($getData)) {
            foreach ($getData as $key => $value) {
                $url .= (strpos($url, '?') !== false ? '&' : '?') . urlencode($key) . '=' . rawurlencode($value);
            }
        }

        $post = (!empty($postData) || $forcePost);
        $c = curl_init($url);

        if ($post) {
            curl_setopt($c, CURLOPT_POST, true);
        }

        if (!empty($postData)) {
            $queryData = $isFile ? $postData : http_build_query($postData);
            curl_setopt($c, CURLOPT_POSTFIELDS, $queryData);
        }

        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

        if (Configure::get('Blesta.curl_verify_ssl')) {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, 2);
        } else {
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($c);
        $status = curl_getinfo($c, CURLINFO_HTTP_CODE);

        if ($result == false) {
            $this->logger->error(curl_error($c));
        }

        curl_close($c);

        $this->lastStatus = $status;
        $this->lastResponse = json_decode($result, true);

        return $this->lastResponse;
    }

    /**
     * @return string
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }

    /**
     * @return string
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }
}

class GoGetSSLAuthException extends Exception
{
    /**
     * GoGetSSLAuthException constructor.
     */
    public function __construct()
    {
        parent::__construct('Please authorize first');
    }
}
















