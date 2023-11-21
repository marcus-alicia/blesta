<?php
/**
 * Interfax service for sending faxes
 *
 * @package blesta
 * @subpackage blesta.components.delivery.interfax
 * @copyright Copyright (c) 2011, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Interfax
{
    /**
     * @var string The URL to submit requests to
     */
    private static $url = 'https://ws.interfax.net/dfs.asmx';
    /**
     * @var string The account user name
     */
    private $username;
    /**
     * @var string The account password
     */
    private $password;
    /**
     * @var array The fax numbers to send to
     */
    private $fax_numbers = [];
    /**
     * @var array The contact names to send to
     */
    private $contacts = [];
    /**
     * @var array An array of bitstream data, file types, and file sizes
     * representing each file to send
     */
    private $files = [];
    /**
     * @var array An array of available file types accepted by Interfax
     */
    private $available_file_types = ['HTML', 'DOC', 'PDF'];
    /**
     * @var array An array of available page sizes accepted by Interfax
     */
    private $available_page_sizes = ['A4', 'Letter', 'Legal', 'B4'];
    /**
     * @var array An array of available page orientations
     */
    private $available_page_orientations = ['Landscape', 'Portrait'];
    /**
     * @var string The subject displayed in the Outbound Queue in Interfax
     */
    private $subject;
    /**
     * @var string The quality of the resolution: 0 for standard, 1 for fine
     */
    private $resolution;
    /**
     * @var string The caller ID that is displayed
     */
    private $csid;
    /**
     * @var string A single email address to send a confirmation to
     */
    private $email_confirmation;
    /**
     * @var string The atomic datetime to send the fax. A time in the past sends immediately
     */
    private $postpone_time;
    /**
     * @var string The page size of the documents sent
     */
    private $page_size;
    /**
     * @var string The page orientation of the documents
     */
    private $page_orientation;
    /**
     * @var DataStructureArray The data structure array helper object
     */
    private $ArrayHelper;

    /**
     * Constructs a new Interfax component
     */
    public function __construct()
    {
        Loader::loadHelpers($this, ['Date', 'DataStructure']);
        Loader::loadComponents($this, ['Input']);
        $this->ArrayHelper = $this->DataStructure->create('Array');

        // Set all vars to default values
        $this->resetAll();
    }

    /**
     * Sets the username and password for the Interfax account to send faxes from
     *
     * @param string $username The account username
     * @param string $password The account password
     */
    public function setAccount($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Sets the fax numbers to use for this fax
     *
     * @param mixed $fax_numbers An array of fax numbers, or a single fax number
     */
    public function setNumbers($fax_numbers)
    {
        // Set fax numbers, digits only
        if (is_array($fax_numbers)) {
            foreach ($fax_numbers as $number) {
                $this->fax_numbers[] = preg_replace('/[^0-9]/', '', $number);
            }
        } else {
            $this->fax_numbers[] = preg_replace('/[^0-9]/', '', $fax_numbers);
        }
    }

    /**
     * Sets the contact names associated with a fax number. Fax numbers and
     * contact names must maintain order.
     *
     * @param mixed $contacts An array of contact names, or a single contact name
     */
    public function setContacts($contacts)
    {
        if (is_array($contacts)) {
            foreach ($contacts as $name) {
                $this->contacts[] = $name;
            }
        } else {
            $this->contacts[] = $contacts;
        }
    }

    /**
     * Sets the file and file type of the file to be faxed
     *
     * @param array $files A numerically-indexed array of files and their types:
     *
     *  - file The bitstream of the file to send in binary
     *  - type The type of file this is (i.e. HTML, DOC, or PDF), (optional, default PDF)
     */
    public function setFile(array $files)
    {
        $j = count($this->files);

        for ($i = 0, $num_files = count($files); $i < $num_files; $i++) {
            // Set files and file types
            if (!empty($files[$i]['file'])) {
                $this->files[$j]['file'] = base64_encode($files[$i]['file']);
                $this->files[$j]['size'] = strlen($files[$i]['file']);

                // Set the file type
                if (!empty($files[$i]['type']) && in_array(strtoupper($files[$i]['type']), $this->getFileTypes())) {
                    $this->files[$j]['type'] = strtoupper($files[$i]['type']);
                } else {
                    $this->files[$j]['type'] = 'PDF';
                }
            }
        }
    }

    /**
     * Retrieves a list of available file types accepted by Interfax
     *
     * @return array A numerically-indexed array of available file types
     */
    public function getFileTypes()
    {
        return $this->available_file_types;
    }

    /**
     * Retrieves a list of available page sizes accepted by Interfax
     *
     * @return array A numerically-indexed array of available page sizes
     */
    public function getPageSizes()
    {
        return $this->available_page_sizes;
    }

    /**
     * Retrieves a list of available page orientations
     *
     * @return array A numerically-indexed array of available page orientations
     */
    public function getPageOrientations()
    {
        return $this->available_page_orientations;
    }

    /**
     * Sets the subject of this fax, viewable in the Interfax account under
     * Outpbound Queue
     *
     * @param string $subject The subject name
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Sets the resolution of this fax
     *
     * @param int $resolution The resolution quality of the fax. 1 for fine, 0 for standard
     */
    public function setResolution($resolution)
    {
        if (in_array($resolution, ['0', '1'])) {
            $this->resolution = $resolution;
        }
    }

    /**
     * Sets the caller ID
     *
     * @param string $caller_id The caller ID
     */
    public function setCallerId($caller_id)
    {
        $this->csid = $caller_id;
    }

    /**
     * Sets an email address to send a confirmation to
     *
     * @param string $email_address An email address
     */
    public function setConfirmationEmail($email_address)
    {
        $this->email_confirmation = $email_address;
    }

    /**
     * Sets the date and time at which Interfax should dispatch the fax. Dates
     * in the past will be sent immediately
     *
     * @param string $time A valid PHP date time stamp
     */
    public function setSendTime($time)
    {
        $this->postpone_time = $this->Date->cast($time, Date::ATOM);
    }

    /**
     * Sets the page size of documents sent to Interfax. This applies to all files
     *
     * @param string $page_size The page size to set (i.e. A4, Letter, Legal, or B4)
     */
    public function setPageSize($page_size)
    {
        $page_size = ucwords(strtolower($page_size));

        if (in_array($page_size, $this->getPageSizes())) {
            $this->page_size = $page_size;
        }
    }

    /**
     * Sets the page orientation of documents sent to Interfax. This applies to all files
     *
     * @param string $orientation The page orientation to set (i.e. Landscape or Portrait)
     */
    public function setPageOrientation($orientation)
    {
        $$orientation = ucwords(strtolower($orientation));

        if (in_array($orientation, $this->getPageOrientations())) {
            $this->page_orientation = $orientation;
        }
    }

    /**
     * Resets all settings back to default except for the account username and password
     */
    public function resetAll()
    {
        $this->fax_numbers = [];
        $this->contacts = [];
        $this->files = [];
        $this->subject = null;
        $this->resolution = '1';
        $this->csid = null;
        $this->email_confirmation = null;
        $this->postpone_time = '2001-12-31T00:00:00-00:00';
        $this->page_size = 'A4';
        $this->page_orientation = 'Portrait';
    }

    /**
     * Creates a list of values separated by $delimiter
     *
     * @param array $field A numerically-indexed array of values to delimit
     * @param string $delimiter The separator to use as the delimiter between values (optional, default ;)
     */
    private function delimitValues(array $field, $delimiter = ';')
    {
        $values = '';

        // Combine values
        for ($i = 0, $num_values = count($field); $i < $num_values; $i++) {
            $values .= $field[$i] . (isset($field[$i + 1]) ? $delimiter : '');
        }

        return $values;
    }

    /**
     * Sends a fax to Interfax
     */
    public function send()
    {
        // Load the HTTP component, if not already loaded
        if (!isset($this->Http)) {
            Loader::loadComponents($this, ['Net']);
            $this->Http = $this->Net->create('Http');
        }

        // Delimit multiple values
        $fax_numbers = $this->delimitValues($this->fax_numbers);
        $contacts = $this->delimitValues($this->contacts);
        $files = '';
        $file_types = '';
        $file_sizes = '';

        if (!empty($this->files)) {
            // Split each key into individual arrays for delimiting
            $split_files = $this->ArrayHelper->numericToKey($this->files);

            // Set file data
            $files = $this->delimitValues($split_files['file']);
            $file_types = $this->delimitValues($split_files['type']);
            $file_sizes = $this->delimitValues($split_files['size']);
        }

        // Create the XML
        $xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/" xmlns:ns7532="http://tempuri.org">
	<SOAP-ENV:Body>
		<SendfaxEx_2 xmlns="http://www.interfax.cc">
			<Username>{$this->username}</Username>
			<Password>{$this->password}</Password>
			<FaxNumbers>{$fax_numbers}</FaxNumbers>
			<Contacts>{$contacts}</Contacts>
			<FilesData>{$files}</FilesData>
			<FileTypes>{$file_types}</FileTypes>
			<FileSizes>{$file_sizes}</FileSizes>
			<Postpone>{$this->postpone_time}</Postpone>
			<PageSize>{$this->page_size}</PageSize>
			<PageOrientation>{$this->page_orientation}</PageOrientation>
			<CSID>{$this->csid}</CSID>
			<Subject>{$this->subject}</Subject>
			<ReplyAddress>{$this->email_confirmation}</ReplyAddress>
			<IsHighResolution>{$this->resolution}</IsHighResolution>
		</SendfaxEx_2>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOT;

        // Send the request to Interfax
        $this->Http->setHeaders(['User-Agent: NuSOAP/0.7.3 (1.114)',
            'Content-Type: text/xml; charset=UTF-8',
            'SOAPAction: "http://www.interfax.cc/SendfaxEx_2"'
        ]);

        $response = $this->Http->post(self::$url, $xml);

        // Parse the response and set any errors
        $this->parseResponse($response);
    }

    /**
     * Parses the SOAP response from InterFax, sets any errors that may have been generated
     *
     * @param string $response The SOAP response from InterFax
     * @return bool true on success, false on error
     */
    private function parseResponse($response)
    {
        // Attempt to parse the response
        $response_code = -1;

        try {
            // Create an XML parser
            $xml = simplexml_load_string($response);

            if (is_object($xml)) {
                // Get the response code (transaction id)
                $temp = (array)$xml->children('soap', true)
                    ->Body
                    ->children('', true)
                    ->SendfaxEx_2Response
                    ->SendfaxEx_2Result;
                $response_code = isset($temp[0]) ? $temp[0] : $response_code;
            }
        } catch (Exception $e) {
            // Error, invalid XML response
        }

        // Set error if response code is invalid (we expect a positive transaction id value)
        if ($response_code <= 0) {
            $this->Input->setErrors([
                'InterFax' => [
                    'response' => $response_code
                ]
            ]);
        }

        if ($response_code > 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns all errors set in this object
     *
     * @return array An array of error info
     */
    public function errors()
    {
        return $this->Input->errors();
    }
}
