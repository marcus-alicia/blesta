<?php
/**
 * OpenSRS Domain Transfer Management
 *
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package opensrs.commands
 */
class OpensrsDomainsTransfer
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
     * Cancels transfers that are pending owner approval.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function cancelTransfer(array $vars) : OpensrsResponse
    {
        return $this->api->submit('cancel_transfer', $vars, 'transfer');
    }

    /**
     * Creates a new order with the same data as a cancelled order.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function processTransfer(array $vars) : OpensrsResponse
    {
        return $this->api->submit('process_transfer', $vars, 'transfer');
    }

    /**
     * Resends an email to the admin contact for a transfer that is in 'pending owner approval' state.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function sendPassword(array $vars) : OpensrsResponse
    {
        return $this->api->submit('send_password', $vars, 'transfer');
    }

    /**
     * Checks to see if the specified domain can be transferred in to OpenSRS, or from one OpenSRS reseller
     * to another.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function checkTransfer(array $vars) : OpensrsResponse
    {
        return $this->api->submit('check_transfer', $vars);
    }

    /**
     * Lists domains that have been transferred away.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getTransfersAway(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_transfers_away', $vars);
    }

    /**
     * Lists domains that have been transferred in.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function getTransfersIn(array $vars) : OpensrsResponse
    {
        return $this->api->submit('get_transfers_in', $vars);
    }

    /**
     * Transfers ownership of a .BE domain.
     *
     * @param array $vars An array of input params including:
     *  -
     * @return OpensrsResponse The response object
     */
    public function tradeDomain(array $vars) : OpensrsResponse
    {
        return $this->api->submit('trade_domain', $vars);
    }
}
