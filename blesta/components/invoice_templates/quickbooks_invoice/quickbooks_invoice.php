<?php

// Use the Quickbooks Invoice PDF renderer for generating PDFs
Loader::load(dirname(__FILE__) . DS . 'quickbooks_invoice_pdf.php');

/**
 * Quickbooks Invoice Template
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates.quickbooks_invoice
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class QuickbooksInvoice extends InvoiceTemplate
{
    /**
     * @var string The version of this template
     */
    private static $version = '1.0.0';
    /**
     * @var string The authors of this template
     */
    private static $authors = [['name' => 'Phillips Data, Inc.', 'url' => 'http://www.blesta.com']];
    /**
     * @var QuickbooksInvoicePdf The PDF object used for rendering
     */
    private $pdf;
    /**
     * @var array An array of meta data for this template
     */
    private $meta = [];
    /**
     * @var stdClass Invoice data for the last invoice set
     */
    private $invoice = [];
    /**
     * @var string MIME type to use when rendering this document
     */
    private $mime_type;

    /**
     * Loads the language to be used for this invoice
     */
    public function __construct()
    {
        // Load language for this template
        Language::loadLang('quickbooks_invoice', null, dirname(__FILE__) . DS . 'language' . DS);
    }
    
    /**
     * Determine whether this invoice template supports quotes
     * 
     * @return boolean
     */
    public function supportsQuotes() {
        return true;
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
     *  - language The language of the definitions to set for this document
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;

        // Load different language for this template if given
        if (isset($meta['language'])) {
            Language::loadLang('quickbooks_invoice', $meta['language'], dirname(__FILE__) . DS . 'language' . DS);
            Language::setLang($meta['language']);
        }

        $font = isset($this->meta['settings']['inv_font_' . Configure::get('Blesta.language')])
            ? $this->meta['settings']['inv_font_' . Configure::get('Blesta.language')]
            : null;

        $this->pdf = new QuickbooksInvoicePdf('P', 'px', $this->meta['paper_size'], true, 'UTF-8', false, $font);

        // Set the meta data to use for this invoice
        $this->pdf->meta = $this->meta;
    }

    /**
     * Sets whether the to address should be included in the invoice
     *
     * @param bool $include_address Whether to include the address in the PDF
     */
    public function includeAddress($include_address = true)
    {
        $this->pdf->include_address = (bool)$include_address;
    }

    /**
     * Sets the CurrencyFormat object for parsing currency values
     *
     * @param CurrencyFormat $currency_format The CurrencyFormat object
     */
    public function setCurrency(CurrencyFormat $currency_format)
    {
        $this->pdf->CurrencyFormat = $currency_format;
    }

    /**
     * Sets the Date object for parsing date values
     *
     * @param Date $date The Date object
     */
    public function setDate(Date $date)
    {
        $this->pdf->Date = $date;
    }

    /**
     * Sets the MIME type to be used when fetching and streaming this invoice.
     * Called after __construct()
     *
     * @param string $mime_type The mime_type to render ("application/pdf", "text/html", etc.)
     */
    public function setMimeType($mime_type)
    {
        $this->mime_type = $mime_type;
    }

    /**
     * Returns the MIME types that this template supports for output
     */
    public function supportedMimeTypes()
    {
        return ['application/pdf'];
    }

    /**
     * Returns the file extension for the given (supported) mime type
     *
     * @param string $mime_type The mime_type to fetch the extension of
     * @return string The extension to use for the given mime type
     */
    public function getFileExtension($mime_type)
    {
        switch ($mime_type) {
            case 'application/pdf':
                return 'pdf';
        }
        return null;
    }

    /**
     * Returns the name of this invoice PDF template
     */
    public function getName()
    {
        return Language::_('QuickbooksInvoice.name', true);
    }

    /**
     * Returns the version of this invoice PDF template
     *
     * @return string The current version of this invoice PDF template
     */
    public function getVersion()
    {
        return self::$version;
    }

    /**
     * Returns the name and URL for the authors of this invoice PDF template
     *
     * @return array The name and URL of the authors of this invoice PDF template
     */
    public function getAuthors()
    {
        return self::$authors;
    }

    /**
     * Generates one or more invoices for a single document
     *
     * @param array $invoice_data An numerically indexed array of stdClass objects each representing an invoice
     */
    public function makeDocument($invoice_data)
    {
        $num_invoices = is_array($invoice_data) ? count($invoice_data) : 0;

        // Loop through all of the given invoices
        for ($i = 0; $i < $num_invoices; $i++) {
            // Set the invoice data for this invoice
            $this->invoice = $invoice_data[$i];

            // Change the language back to the previous language if it has been changed
            if (isset($prev_language)) {
                Language::setLang($prev_language);
            }

            // If no 'global' language is given, default to using the invoice client's language
            if (empty($this->meta['language']) && isset($this->invoice->client->settings['language'])) {
                Language::loadLang(
                    'quickbooks_invoice',
                    $this->invoice->client->settings['language'],
                    dirname(__FILE__) . DS . 'language' . DS
                );
                $prev_language = Language::setLang($this->invoice->client->settings['language']);
            }

            // Set the invoice data for this PDF
            $this->pdf->invoice = $this->invoice;

            // Start a new page group for each individual invoice
            $this->pdf->startPageGroup();

            // Add a new page so that each group starts on its own page
            $this->pdf->AddPage();

            // Draw all line items for this invoice
            $this->pdf->drawInvoice();
        }
    }

    /**
     * Returns the invoice document in the desired format
     *
     * @return string The PDF document in binary format
     */
    public function fetch()
    {
        switch ($this->mime_type) {
            case 'application/pdf':
                return $this->pdf->Output(null, 'S');
        }

        return null;
    }

    /**
     * Outputs the Invoice document to stdout, sending the apporpriate headers to render the document inline
     *
     * @param string $name The name for the document minus the extension (optional)
     * @throws Exception Thrown when the MIME type is not supported by the template
     */
    public function stream($name = null)
    {
        $name = $name === null ? $this->invoice->id_code : $name;

        switch ($this->mime_type) {
            case 'application/pdf':
                $this->pdf->Output($name . '.' . $this->getFileExtension($this->mime_type), 'I');
                exit;
        }
        throw new Exception('MIME Type: ' . $this->mime_type . ' not supported');
    }

    /**
     * Outputs the Invoice document to stdout, sending the appropriate headers to force a download of the document
     *
     * @param string $name The name for the document minus the extension (optional)
     * @throws Exception Thrown when the MIME type is not supported by the template
     */
    public function download($name = null)
    {
        $name = $name === null ? $this->invoice->id_code : $name;

        switch ($this->mime_type) {
            case 'application/pdf':
                $this->pdf->Output($name . '.' . $this->getFileExtension($this->mime_type), 'D');
                exit;
        }
        throw new Exception('MIME Type: ' . $this->mime_type . ' not supported');
    }
}
