<?php
/**
 * OpenSRS SSL Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsSsl
{
    /**
     * @var OpensrsApi
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param OpensrsApi $api The API to use for communication
     */
    public function __construct(OpensrsApi $api)
    {
        $this->api = $api;
    }

    /**
     * Initiates a SSL certificate order. Please note your handle parameter: "save"- will keep the order
     * pending for later approval by the RSP, whereas "process" will proceed and complete the order.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function swRegister(array $vars) : OpensrsResponse
    {
        return $this->api->submit('sw_register', $vars, 'trust_service');
    }

    /**
     * Cancel a SSL Service 30 day free trial order.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function cancelFreeTrial(array $vars) : OpensrsResponse
    {
        return $this->api->submit('cancel_free_trial', $vars, 'trust_service');
    }

    /**
     * Cancel a SSL Service order.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function cancelOrder(array $vars) : OpensrsResponse
    {
        return $this->api->submit('cancel_order', $vars, 'trust_service');
    }

    /**
     * Creates a SiteLock or TRUSTe account so that users can log in and manage the Trust Service product.
     * To use this command, the order cannot be in the pending state.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function createToken(array $vars) : OpensrsResponse
    {
        return $this->api->submit('create_token', $vars, 'trust_service');
    }

    /**
     * Returns the certificate for the specified SSL service product as well as associated product
     * information.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getCert(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_cert', $vars, 'trust_service');
    }

    /**
     * Queries the properties of the specified SSL Service product.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getProductInfo(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_product_info', $vars, 'trust_service');
    }

    /**
     * Get products.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getProducts(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_products', $vars, 'trust_service');
    }

    /**
     * Parses the CSR and identifies its data elements.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function parseCsr(array $vars) : OpensrsResponse
    {
        return $this->api->submit('parse_csr', $vars, 'trust_service');
    }

    /**
     * Queries the list of approvers for the SSL service that is associated with a specified domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function queryApproverList(array $vars) : OpensrsResponse
    {
        return $this->api->submit('query_approver_list', $vars, 'trust_service');
    }

    /**
     * If you have a Symantec or SiteLock seal, or the GeoTrust Web Site Anti- Malware Scan product, and
     * you have corrected a malware issue on your site, you can ask the SSL Service provider to rescan your
     * system immediately and reinstate the Seal.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function requestOnDemandScan(array $vars) : OpensrsResponse
    {
        return $this->api->submit('request_on_demand_scan', $vars, 'trust_service');
    }

    /**
     * Resends the certificate email.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function resendCertEmail(array $vars) : OpensrsResponse
    {
        return $this->api->submit('resend_cert_email', $vars, 'trust_service');
    }

    /**
     * Resends the Approver email.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function resendApproveEmail(array $vars) : OpensrsResponse
    {
        return $this->api->submit('resend_approve_email', $vars, 'trust_service');
    }

    /**
     * Submits a SSL Service order update to the OpenSRS system. When updating existing SSL Service orders,
     * the general rules are: Include the parameters and values that you want to change. To remove a remove
     * a value, submit the parameter with an empty value, omit any parameters that you do not want to
     * change.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function updateOrder(array $vars) : OpensrsResponse
    {
        return $this->api->submit('update_order', $vars, 'trust_service');
    }

    /**
     * Updates the SSL Service product and enables or disables the Symantec SSL Seal and/or the Symantec
     * Search-in-Seal.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function updateProduct(array $vars) : OpensrsResponse
    {
        return $this->api->submit('update_product', $vars, 'trust_service');
    }

    /**
     * Changes the authentication method for a domain-vetted RapidSSL, Geotrust or Comodo certificate, or
     * sends an immediate request for validation of a domain-vetted RapidSSL.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function updateDvAuth(array $vars) : OpensrsResponse
    {
        return $this->api->submit('update_dv_auth', $vars, 'trust_service');
    }
}
