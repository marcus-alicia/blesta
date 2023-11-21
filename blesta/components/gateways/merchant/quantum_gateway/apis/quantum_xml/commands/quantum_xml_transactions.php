<?php
/**
 * Quantum XML Requester Transaction Management
 *
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package quantum_xml.commands
 */
class QuantumXmlTransactions
{
    /**
     * @var QuantumXml
     */
    private $api;

    /**
     * Sets the API to use for communication
     *
     * @param QuantumXml $api The API to use for communication
     */
    public function __construct(QuantumXml $api)
    {
        $this->api = $api;
    }

    /**
     * Used as a search engine to find transactions based on request parameters
     *
     * @param array $vars An array of input params including:
     *  - Status
     *  - TransactionDateBegin
     *  - TransactionDateEnd
     *  - TransactionType
     *  - CustomerID
     *  - PaymentType
     *  - SettlementBatchID
     *  - SettlementDateBegin
     *  - SettlementDateEnd
     *  - RecurrID
     * @return QuantumXmlResponse
     */
    public function search(array $vars)
    {
        return $this->api->submit('SearchTransactions', $vars);
    }

    /**
     * Used to create an individual Debit or Credit Transaction from Vault Customers
     *
     * @param array $vars An array of input params including:
     *  - TransactionType One of:
     *      - CREDIT for CC
     *      - DEBIT for EFT
     *  - CustomerID
     *  - Memo
     *  - Amount
     *  - TransactionDate
     *  - ProcessType One of:
     *      - AUTH_CAPTURE (CC only)
     *      - AUTH_ONLY (CC only)
     *      - SALES
     * @return QuantumXmlResponse
     */
    public function create(array $vars)
    {
        return $this->api->submit('CreateTransaction', $vars);
    }

    /**
     * Used to create an individual Debit/Credit Transaction
     *
     * @param array $vars An array of input params including:
     *  - TransactionID
     *  - TransactionType One of:
     *      - CREDIT for CC
     *      - DEBIT for EFT
     *  - ProcessType One of:
     *      - AUTH_CAPTURE (CC only)
     *      - AUTH_ONLY (CC only)
     *      - SALES
     *      - RETURN
     *      - VOID
     *      - PREVIOUS_SALE
     *  - PaymentType One of:
     *      - CC
     *      - EFT
     *  - Amount The payment amount
     *  - CreditCardNumber
     *  - ExpireMonth
     *  - ExpireYear
     *  - CVV2
     *  - ABANumber
     *  - AccountNumber
     *  - EFTType
     *  - AccountType (Checking/Savings)
     *  - Memo
     *  - FirstName
     *  - LastName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - Country
     *  - EmailAddress
     *  - PhoneNumber
     *  - IPAddress
     *  - InvoiceNumber
     *  - InvoiceDescription
     *  - TransactionDate
     *  - RID
     *  - CustomerID
     *  - RecurAmount
     *  - RecurTimes
     *  - MerchantEmail
     *  - CustomerEmail
     *  - OverRideRecureDay
     *  - RequestType2
     *  - EnablePartialAuths
     *  - PartialPaymentID
     * @return QuantumXmlResponse
     */
    public function processSingle(array $vars)
    {
        return $this->api->submit('ProcessSingleTransaction', $vars);
    }

    /**
     * Charge a customer from a previously APPROVED transaction
     *
     * @param array $vars An array of input params including:
     *  - TransactionID
     *  - Amount
     *  - InvoiceNumber
     *  - InvoiceDescription
     * @return QuantumXmlResponse
     */
    public function resubmit(array $vars)
    {
        return $this->api->submit('ResubmitTransaction', $vars);
    }
}
