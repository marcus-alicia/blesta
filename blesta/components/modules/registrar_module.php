<?php
/**
 * Abstract class that all Registrar Modules must extend
 *
 * @package blesta
 * @subpackage blesta.components.modules
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class RegistrarModule extends Module
{
    /**
     * Used to determine whether this module supports DNS Management
     *
     * @return bool True to say that this module uses the dns_management configurable option, false otherwise
     */
    public function supportsDnsManagement()
    {
        if (isset($this->config->features->dns_management)) {
            return $this->config->features->dns_management;
        }

        return false;
    }

    /**
     * Used to determine whether this module supports Email Forwarding
     *
     * @return bool True to say that this module uses the email_forwarding configurable option, false otherwise
     */
    public function supportsEmailForwarding()
    {
        if (isset($this->config->features->email_forwarding)) {
            return $this->config->features->email_forwarding;
        }

        return false;
    }

    /**
     * Used to determine whether this module supports ID Protection
     *
     * @return bool True to say that this module uses the id_protection configurable option, false otherwise
     */
    public function supportsIdProtection()
    {
        if (isset($this->config->features->id_protection)) {
            return $this->config->features->id_protection;
        }

        return false;
    }

    /**
     * Used to determine whether this module supports EPP Code
     *
     * @return bool True to say that this module uses the epp_code configurable option, false otherwise
     */
    public function supportsEppCode()
    {
        if (isset($this->config->features->epp_code)) {
            return $this->config->features->epp_code;
        }

        return false;
    }

    /**
     * Checks if this registrar supports a given feature
     *
     * @param string $feature_name The name of the feature for which to check support (e.g. id_protection)
     */
    public function supportsFeature($feature_name)
    {
        $method_name = 'supports' . str_replace('_', '', ucwords($feature_name, '_'));
        return method_exists($this, $method_name) && $this->{$method_name}();
    }

    /**
     * Verifies that the provided domain name is available
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available, false otherwise
     */
    public function checkAvailability($domain, $module_row_id = null)
    {
        return true;
    }

    /**
     * Verifies that the provided domain name is available for transfer
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain is available for transfer, false otherwise
     */
    public function checkTransferAvailability($domain, $module_row_id = null)
    {
        return true;
    }

    /**
     * Verifies if a domain of the provided TLD can be registered or transfer by the provided term
     *
     * @param string $tld The TLD to verify
     * @param int $term The term in which the domain name will be registered or transferred (in years)
     * @param bool $transfer True if the domains is going to be transferred, false otherwise (optional)
     * @return bool True if the term is valid for the current TLD
     */
    public function isValidTerm($tld, $term, $transfer = false)
    {
        if ($term > 10) {
            return false;
        }

        return true;
    }

    /**
     * Gets a list of contacts associated with a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of contact objects with the following information:
     *
     *  - external_id The ID of the contact in the registrar
     *  - email The primary email associated with the contact
     *  - phone The phone number associated with the contact
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - address1 The contact's address
     *  - address2 The contact's address line two
     *  - city The contact's city
     *  - state The 3-character ISO 3166-2 subdivision code
     *  - zip The zip/postal code for this contact
     *  - country The 2-character ISO 3166-1 country code
     */
    public function getDomainContacts($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return [];
    }

    /**
     * Updates the list of contacts associated with a domain
     *
     * @param string $domain The domain for which to update contact info
     * @param array $vars A list of contact arrays with the following information:
     *
     *  - external_id The ID of the contact in the registrar (optional)
     *  - email The primary email associated with the contact
     *  - phone The phone number associated with the contact
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - address1 The contact's address
     *  - address2 The contact's address line two
     *  - city The contact's city
     *  - state The 3-character ISO 3166-2 subdivision code
     *  - zip The zip/postal code for this contact
     *  - country The 2-character ISO 3166-1 country code
     *  - * Other fields required by the registrar
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the contacts were updated, false otherwise
     */
    public function setDomainContacts($domain, array $vars = [], $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return [];
    }

    /**
     * Gets a list of basic information for a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of common domain information
     *
     *  - * The contents of the return vary depending on the registrar
     */
    public function getDomainInfo($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return [];
    }

    /**
     * Returns whether the domain has a registrar lock
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain has a registrar lock, false otherwise
     */
    public function getDomainIsLocked($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Gets a list of name server data associated with a domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of name servers, each with the following fields:
     *
     *  - url The URL of the name server
     *  - ips A list of IPs for the name server
     */
    public function getDomainNameServers($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return [];
    }

    /**
     * Assign new name servers to a domain
     *
     * @param string $domain The domain for which to assign new name servers
     * @param int|null $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of name servers to assign (e.g. [ns1, ns2])
     * @return bool True if the name servers were successfully updated, false otherwise
     */
    public function setDomainNameservers($domain, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Assigns new ips to a name server
     *
     * @param array $vars A list of name servers and their new ips
     *
     *  - nsx => [ip1, ip2]
     * @param int|null $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the name servers were successfully updated, false otherwise
     */
    public function setNameserverIps(array $vars = [], $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Gets the domain expiration date
     *
     * @param stdClass $service The service belonging to the domain to lookup
     * @param string $format The format to return the expiration date in
     * @return string The domain expiration date in UTC time in the given format
     * @see Services::get()
     */
    public function getExpirationDate($service, $format = 'Y-m-d H:i:s')
    {
        if (!isset($this->PluginManager)) {
            Loader::loadModels($this, ['PluginManager']);
        }
        if (!isset($this->Companies)) {
            Loader::loadModels($this, ['Companies']);
        }
        if (!isset($this->Record)) {
            Loader::loadComponents($this, ['Record']);
        }

        // Get the expiration from the domain manager it is installed
        if ($this->PluginManager->isInstalled('domains', Configure::get('Blesta.company_id'))) {
            // Fetch the locally stored expiration
            $domain = $this->Record->select()
                ->from('domains_domains')
                ->where('service_id', '=', $service->id)
                ->fetch();
            $local_expiration_date = $domain->expiration_date ?? null;

            if ($local_expiration_date) {
                // There is no expiration date stored locally
                if (strtotime($local_expiration_date) >= strtotime($service->date_renews)) {
                    // Return the locally stored expiration if it the renew date preceeds it
                    return $local_expiration_date;
                } else {
                    // Return the service renew date + the domains_renewal_days_before_expiration setting as the
                    // expiration date
                    $renewal_days = $this->Companies->getSetting(
                        Configure::get('Blesta.company_id'),
                        'domains_renewal_days_before_expiration'
                    );

                    return $this->Companies->Date->modify(
                        $service->date_renews,
                        '+' . ($renewal_days->value ?? 0) . ' days',
                        $format,
                        Configure::get('Blesta.company_timezone')
                    );
                }
            }
        }

        if (isset($service->date_renews)) {
            return $service->date_renews;
        }

        return null;
    }

    /**
     * Locks the given domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain was successfully locked, false otherwise
     */
    public function lockDomain($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Unlocks the given domain
     *
     * @param string $domain The domain to lookup
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the domain was successfully unlocked, false otherwise
     */
    public function unlockDomain($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Register a new domain through the registrar
     *
     * @param string $domain The domain to register
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the registration request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully registered, false otherwise
     */
    public function registerDomain($domain, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Transfer a domain through the registrar
     *
     * @param string $domain The domain to register
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the transfer request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully transferred, false otherwise
     */
    public function transferDomain($domain, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Resend domain transfer verification email
     *
     * @param string $domain The domain for which to resend the email
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the email was successfully sent, false otherwise
     */
    public function resendTransferEmail($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Send domain transfer auth code to admin email
     *
     * @param string $domain The domain for which to send the email
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return bool True if the email was successfully sent, false otherwise
     */
    public function sendEppEmail($domain, $module_row_id = null)
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Set a new domain transfer auth code
     *
     * @param string $domain The domain for which to update the code
     * @param string $epp_code The new epp auth code to use
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the update request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the code was successfully updated, false otherwise
     */
    public function updateEppCode($domain, $epp_code, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Renew a domain through the registrar
     *
     * @param string $domain The domain to renew
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the renew request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully renewed, false otherwise
     */
    public function renewDomain($domain, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Restore a domain through the registrar
     *
     * @param string $domain The domain to restore
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $vars A list of vars to submit with the restore request
     *
     *  - * The contents of $vars vary depending on the registrar
     * @return bool True if the domain was successfully restored, false otherwise
     */
    public function restoreDomain($domain, $module_row_id = null, array $vars = [])
    {
        if (isset($this->Input)) {
            $this->Input->setErrors($this->getCommonError('unsupported'));
        }

        return false;
    }

    /**
     * Gets the domain name from the given service
     *
     * @param stdClass $service The service from which to extract the domain name
     * @return string The domain name associated with the service
     * @see Services::get()
     */
    public function getServiceDomain($service)
    {
        if (isset($service->fields)) {
            foreach ($service->fields as $service_field) {
                if ($service_field->key == 'domain') {
                    return $service_field->value;
                }
            }
        }

        return $this->getServiceName($service);
    }

    /**
     * Get a list of the TLDs supported by the registrar module
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs supported by the registrar module
     */
    public function getTlds($module_row_id = null)
    {
        return [];
    }

    /**
     * Get a list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getTldPricing($module_row_id = null)
    {
        return [];
    }

    /**
     * Get a filtered list of the TLD prices
     *
     * @param int $module_row_id The ID of the module row to fetch for the current module
     * @param array $filters A list of criteria by which to filter fetched pricings including but not limited to:
     *
     *  - tlds A list of tlds for which to fetch pricings
     *  - currencies A list of currencies for which to fetch pricings
     *  - terms A list of terms for which to fetch pricings
     * @return array A list of all TLDs and their pricing
     *    [tld => [currency => [year# => ['register' => price, 'transfer' => price, 'renew' => price]]]]
     */
    public function getFilteredTldPricing($module_row_id = null, $filters = [])
    {
        return $this->getTldPricing($module_row_id);
    }

    /**
     * Returns the type of this module
     *
     * @return string The type of this module
     */
    public function getType()
    {
        if (isset($this->config->type)) {
            return $this->config->type;
        }

        return 'registrar';
    }
}
