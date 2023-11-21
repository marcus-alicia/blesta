<?php
/**
 * Quantum XML Requester Customer Management
 *
 * @copyright Copyright (c) 2014, Phillips Data, Inc.
 * @license http://opensource.org/licenses/mit-license.php MIT License
 * @package quantum_xml.commands
 */
class QuantumXmlCustomers
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
     * Adds a new Customer to the Vault
     *
     * @param array $vars An array of input params including:
     *  - CustomerID - A unique identifier for the customer
     *  - FirstName
     *  - LastName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - PhoneNumber
     *  - EmailAddress
     *  - PaymentType
     *  - CreditCardNumber
     *  - CVV2
     *  - ExpireMonth
     *  - ExpireYear
     *  - ABANumber
     *  - AccountNumber
     *  - EFTType - PPD/CCD/WEB/TEL
     * @return QuantumXmlResponse
     */
    public function add(array $vars)
    {
        return $this->api->submit('CustomerAdd', $vars);
    }

    /**
     * Update a current Vault Customer's information
     *
     * @param array $vars An array of input params including:
     *  - CustomerID - A unique identifier for the customer
     *  - FirstName
     *  - LastName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - PhoneNumber
     *  - EmailAddress
     *  - PaymentType
     *  - CreditCardNumber
     *  - CVV2
     *  - ExpireMonth
     *  - ExpireYear
     *  - ABANumber
     *  - AccountNumber
     *  - EFTType - PPD/CCD/WEB/TEL
     * @return QuantumXmlResponse
     */
    public function update(array $vars)
    {
        return $this->api->submit('CustomerUpdate', $vars);
    }


    /**
     * Updates the credit card number and expiration date or updates an EFT routing and account number
     *
     * @param array $vars An array of input params including:
     *  - CustomerID - A unique identifier for the customer
     *  - PaymentType
     *  - CreditCardNumber
     *  - CVV2
     *  - ExpireMonth
     *  - ExpireYear
     *  - ABANumber
     *  - AccountNumber
     *  - EFTType - PPD/CCD/WEB/TEL
     * @return QuantumXmlResponse
     */
    public function updatePayOnly(array $vars)
    {
        return $this->api->submit('CustomerUpdatePayOnly', $vars);
    }

    /**
     * Adds a new Customer to the Vault or Updates current customer
     *
     * @param array $vars An array of input params including:
     *  - CustomerID - A unique identifier for the customer
     *  - FirstName
     *  - LastName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - PhoneNumber
     *  - EmailAddress
     *  - PaymentType
     *  - CreditCardNumber
     *  - CVV2
     *  - ExpireMonth
     *  - ExpireYear
     *  - ABANumber
     *  - AccountNumber
     *  - EFTType - PPD/CCD/WEB/TEL
     * @return QuantumXmlResponse
     */
    public function addUpdate(array $vars)
    {
        return $this->api->submit('AddUpdateCustomer', $vars);
    }


    /**
     * Remove a current Vault Customers information
     *
     * @param array $vars An array of input params including:
     *  - CustomerID - A unique identifier for the customer
     * @return QuantumXmlResponse
     */
    public function remove(array $vars)
    {
        return $this->api->submit('CustomerRemove', $vars);
    }

    /**
     * Search for Vault Customers who’s credit card is expiring
     *
     * @param array $vars An array of input params including:
     *  - NumMonths
     * @return QuantumXmlResponse
     */
    public function expiring(array $vars)
    {
        return $this->api->submit('ExpiringVaultCustomers', $vars);
    }

    /**
     * Used as a search engine to search for customers in the vault
     *
     * @param array $vars An array of input params including:
     *  - FirstName
     *  - LastName
     *  - Address
     *  - ZipCode
     *  - EmailAddress
     *  - PaymentType
     * @return QuantumXmlResponse
     */
    public function search(array $vars)
    {
        return $this->api->submit('SearchVault', $vars);
    }

    /**
     * Used as a search engine to search for customers in the vault
     *
     * @param array $vars An array of input params including:
     *  - CustomerID
     * @return QuantumXmlResponse
     */
    public function show(array $vars)
    {
        return $this->api->submit('ShowVaultDetails', $vars);
    }

    /**
     * Search for Recurring Customers who’s credit card is expiring
     *
     * @param array $vars An array of input params including:
     *  - NumMonths
     * @return QuantumXmlResponse
     */
    public function expiringRecurring(array $vars)
    {
        return $this->api->submit('ExpiringRecurringCustomers', $vars);
    }

    /**
     * Search for Recurring Entries
     *
     * @param array $vars An array of input params including:
     *  - PaymentType
     *  - Amount
     *  - BillingName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - Country
     *  - PhoneNumber
     *  - EmailAddress
     *  - CustomerID
     *  - Status
     * @return QuantumXmlResponse
     */
    public function searchRecurring(array $vars)
    {
        return $this->api->submit('SearchRecurring', $vars);
    }

    /**
     * Search for Recurring Entries
     *
     * @param array $vars An array of input params including:
     *  - RecurrID
     * @return QuantumXmlResponse
     */
    public function showRecurring(array $vars)
    {
        return $this->api->submit('ShowRecurringCustomer', $vars);
    }

    /**
     * Search for Recurring Entries
     *
     * @param array $vars An array of input params including:
     *  - RecurrID
     *  - CustomerID
     *  - BillingName
     *  - Address
     *  - City
     *  - State
     *  - ZipCode
     *  - Country
     *  - PhoneNumber
     *  - EmailAddress
     *  - Amount
     *  - CreditCardNumber
     *  - ExpireMonth
     *  - ExpireYear
     *  - PaymentType
     *  - RecurStatus (Active/Suspended/Canceled)
     * @return QuantumXmlResponse
     */
    public function updateRecurring(array $vars)
    {
        return $this->api->submit('UpdateRecurringCustomer', $vars);
    }

    /**
     * Used to create an individual Debit or Credit Transaction from Vault Customers
     *
     * @param array $vars An array of input params including:
     *  - CustomerID
     *  - RecurAmount
     *  - RID
     *  - RecurDay
     *  - RecurTimes
     * @return QuantumXmlResponse
     */
    public function createRecurring(array $vars)
    {
        return $this->api->submit('VaultCreateRecurring', $vars);
    }
}
