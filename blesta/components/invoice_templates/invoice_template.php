<?php
/**
 * Abstract class that all Invoice Templates must extend
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class InvoiceTemplate
{
    /**
     * Determine whether this invoice template supports quotes
     * 
     * @return boolean
     */
    public function supportsQuotes() {
        return false;
    }

    /**
     * Sets the meta data to use for this invoice. This method is invoked after
     * __construct() but before makeDocument()
     *
     * @param array $meta An array of meta data including:
     *
     *  - background The absolute path to the background graphic
     *  - logo The absolute path to the logo graphic
     *  - company_name The name of the company
     *  - company_address The address of the company
     *  - terms The terms to display on this invoice
     *  - paper_size The size of paper to use (e.g. "A4" or "Letter")
     *  - tax An array of tax info including:
     *      - tax_id The Tax ID/VATIN of this company
     *      - cascade_tax Whether or not taxes are cascading
     */
    abstract public function setMeta($meta);

    /**
     * Sets the CurrencyFormat object for parsing currency values
     *
     * @param CurrencyFormat $currency_format The CurrencyFormat object
     */
    abstract public function setCurrency(CurrencyFormat $currency_format);

    /**
     * Sets the Date object for parsing date values
     *
     * @param Date $date The Date object
     */
    public function setDate(Date $date)
    {
    }

    /**
     * Sets the MIME type to be used when fetching and streaming this invoice.
     * Called after __construct()
     *
     * @param string $mime_type The mime_type to render ("application/pdf", "text/html", etc.)
     */
    abstract public function setMimeType($mime_type);

    /**
     * Returns the MIME types that this template supports for output
     */
    abstract public function supportedMimeTypes();

    /**
     * Returns the file extension for the given (supported) mime type
     *
     * @param string $mime_type The mime_type to fetch the extension of
     * @return string The extension to use for the given mime type
     */
    abstract public function getFileExtension($mime_type);

    /**
     * Returns the name of this invoice PDF template
     */
    abstract public function getName();

    /**
     * Returns the version of this invoice PDF template
     *
     * @return string The current version of this invoice PDF template
     */
    abstract public function getVersion();

    /**
     * Returns the name and URL for the authors of this invoice PDF template
     *
     * @return array The name and URL of the authors of this invoice PDF template
     */
    abstract public function getAuthors();

    /**
     * Generates one or more invoices for a single document
     *
     * @param array $invoice_data An numerically indexed array of stdClass objects each representing an invoice
     * @see Invoices::get()
     */
    abstract public function makeDocument($invoice_data);

    /**
     * Sets whether or not to include the To address information in the invoice document
     *
     * @param bool $include_address True to include the to address information, false to leave the information
     *  off of the document
     */
    abstract public function includeAddress($include_address = true);

    /**
     * Returns the invoice document in the desired format
     *
     * @return string The document in binary format
     */
    abstract public function fetch();

    /**
     * Outputs the Invoice document to stdout, sending the apporpriate headers to render the document inline
     *
     * @param string $name The name of the document minus the extension (optional).
     */
    abstract public function stream($name = null);

    /**
     * Outputs the Invoice document to stdout, sending the appropriate headers to force a download of the document
     *
     * @param string $name The name of the document minus the extension (optional).
     */
    abstract public function download($name = null);
}
