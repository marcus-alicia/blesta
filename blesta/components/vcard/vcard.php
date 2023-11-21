<?php

use JeroenDesloovere\VCard\VCard as VirtualContact;

/**
 * vCard component that creates vCard-formatted address book data
 *
 * @package blesta
 * @subpackage blesta.components.vcard
 * @copyright Copyright (c) 2010-2018, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class VCard
{
    /**
     * @var A class_vcard instance
     */
    private $vCard;

    /**
     * Set default vCard data
     */
    public function __construct()
    {
        $this->vCard = new VirtualContact();
    }

    /**
     * Creates a vCard with the given data
     *
     * @param array $data A list of fields to set in the vCard, including (all optional):
     *
     *  - first_name The first name of the contact
     *  - last_name The last name of the contact
     *  - company The contact's company name
     *  - title The contact's title
     *  - email1 The contact's email address (@deprecated since v4.5.0, use 'email' instead)
     *  - email The contact's email address
     *  - home_address The contact's home street address
     *  - home_city The contact's home city
     *  - home_state The contact's home 2-character state
     *  - home_postal_code The contact's home zip/postal code
     *  - home_country The contact's home 2-character country
     *  - work_address The contact's work street address
     *  - work_city The contact's work city
     *  - work_state The contact's work 2-character state
     *  - work_postal_code The contact's work zip/postal code
     *  - work_country The contact's work 2-character country
     *  - home_tel The contact's home phone number
     *  - cell_tel The contact's cell phone number
     *  - fax_tel The contact's fax number
     *  - office_tel The contact's office phone number (@deprecated since v4.5.0, use 'work_tel' instead)
     *  - work_tel The contact's work phone number
     * @param bool $stream True to stream the vCard for download (optional)
     * @param string $file_name The name of the file to stream (optional)
     * @return string A string representing the vCard
     */
    public function create(array $data, $stream = true, $file_name = null)
    {
        // Replace the deprecated fields with their alternatives
        $data = $this->removeDeprecatedFields($data);

        // Build the vCard
        $this->addName($data);
        $this->addEmail($data);
        $this->addCompany($data);

        $addresses = ['home', 'work'];
        foreach ($addresses as $address) {
            $this->addAddress($data, $address);
        }

        $phones = ['home', 'cell', 'fax', 'work'];
        foreach ($phones as $phone) {
            $this->addPhone($data, $phone);
        }

        $this->setFileName($file_name);

        if ($stream) {
            $this->vCard->download();
            return;
        }

        return $this->vCard->getOutput();
    }

    /**
     * Updates the given data to remove deprecated fields in favor for of their alternative
     *
     * @param array $data An array of data
     * @return array An updated array of data with deprecated fields removed
     * @deprecated since v4.5.0
     */
    private function removeDeprecatedFields(array $data)
    {
        if (array_key_exists('email1', $data)) {
            $data['email'] = (isset($data['email']) ? $data['email'] : $data['email1']);
            unset($data['email1']);
        }

        if (array_key_exists('office_tel', $data)) {
            $data['work_tel'] = (isset($data['work_tel']) ? $data['work_tel'] : $data['office_tel']);
            unset($data['office_tel']);
        }

        return $data;
    }

    /**
     * Set the name to the vCard
     *
     * @param array $data The data to set
     */
    private function addName(array $data)
    {
        $this->vCard->addName(
            (isset($data['last_name']) ? $data['last_name'] : ''),
            (isset($data['first_name']) ? $data['first_name'] : '')
        );
    }

    /**
     * Set the email to the vCard
     *
     * @param array $data The data to set
     * @param string $type The email type (optional) one of:
     *
     *  - home
     *  - work
     */
    private function addEmail(array $data, $type = null)
    {
        // Set the email type
        $emailType = 'PREF';
        switch ($type) {
            case 'home':
                // Intentionally no break
            case 'work':
                $emailType = strtoupper($type);
                break;
        }

        $this->vCard->addEmail((isset($data['email']) ? $data['email'] : ''), $emailType);
    }

    /**
     * Set the company and job title to the vCard
     *
     * @param array $data The data to set
     */
    private function addCompany(array $data)
    {
        $this->vCard->addCompany((isset($data['company']) ? $data['company'] : ''));
        $this->vCard->addJobtitle((isset($data['title']) ? $data['title'] : ''));
    }

    /**
     * Set the address to the vCard
     *
     * @param array $data The data to set
     * @param string $type The address type, one of:
     *
     *  - home
     *  - work
     */
    private function addAddress(array $data, $type)
    {
        // Set the address type
        $prefix = '';
        switch ($type) {
            case 'home':
                // Intentionally no break
            case 'work':
                $prefix = strtolower($type) . '_';
                break;
        }

        // No address provided
        if ($prefix === '') {
            return;
        }

        // Set the address
        $this->vCard->addAddress(
            null,
            null,
            (isset($data[$prefix . 'address']) ? $data[$prefix . 'address'] : ''),
            (isset($data[$prefix . 'city']) ? $data[$prefix . 'city'] : ''),
            (isset($data[$prefix . 'state']) ? $data[$prefix . 'state'] : ''),
            (isset($data[$prefix . 'postal_code']) ? $data[$prefix . 'postal_code'] : ''),
            (isset($data[$prefix . 'country']) ? $data[$prefix . 'country'] : ''),
            $type
        );
    }

    /**
     * Set the phone number to the vCard
     *
     * @param array $data The data to set
     * @param string $type The phone type, one of:
     *
     *  - home
     *  - cell
     *  - work
     *  - fax
     */
    private function addPhone(array $data, $type)
    {
        // Set the phone type
        $phoneType = 'PREF';
        $types = ['work', 'home', 'fax', 'cell'];
        $prefix = null;
        if (in_array($type, $types)) {
            $phoneType = strtoupper($type);
            $prefix = strtolower($type) . '_';
        }

        // No phone provided
        if ($prefix === null || !isset($data[$prefix . 'tel'])) {
            return;
        }

        $this->vCard->addPhoneNumber($data[$prefix . 'tel'], $phoneType);
    }

    /**
     * Set the filename for the vCard
     *
     * @param null|string $fileName The name of the file to set, otherwise defaults to a generic name
     */
    private function setFileName($fileName = null)
    {
        $this->vCard->setFilename(($fileName === null ? 'vcard' : $fileName), true);
    }
}
