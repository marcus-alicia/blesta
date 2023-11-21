<?php

// Uses the TcpdfWrapper for rendering the PDF
Loader::load(COMPONENTDIR . 'invoice_templates' . DS . 'tcpdf_wrapper.php');

/**
 * Quickbooks Invoice Template
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates.quickbooks_invoice
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class QuickbooksInvoicePdf extends TcpdfWrapper
{
    /**
     * @var string Holds the default font size for this document
     */
    private static $font_size = 9;
    /**
     * @var string Holds the alternate font size for this document
     */
    private static $font_size_alt = 7;
    /**
     * @var string Holds the second alternate font size for this document
     */
    private static $font_size_alt2 = 10;
    /**
     * @var string Holds the third alternate font size for this document
     */
    private static $font_size_alt3 = 20;
    /**
     * @var string The primary font family to use
     */
    private $font = 'dejavusanscondensed';
    /**
     * @var array An RGB representation of the primary color used throughout
     */
    private static $primary_color = [0, 0, 0];
    /**
     * @var array An RGB representation of the primary text color used throughout
     */
    private static $primary_text_color = [0, 0, 0];
    /**
     * @var array The standard number format options
     */
    private static $standard_num_options = [
        'prefix' => false,
        'suffix' => false,
        'code' => false,
        'with_separator' => false
    ];
    /**
     * @var array An array of meta data for this invoice
     */
    public $meta = [];
    /**
     * @var CurrencyFormat The CurrencyFormat object used to format currency values
     */
    public $CurrencyFormat;
    /**
     * @var Date The Date object used to format date values
     */
    public $Date;
    /**
     * @var array An array of invoice data for this invoice
     */
    public $invoice = [];
    /**
     * @var int The Y position where the header finishes its content
     */
    private $header_end_y = 0;
    /**
     * @var array An array of line item options
     */
    private $line_options = [];
    /**
     * @var array An array of transaction payment row options
     */
    private $payment_options = [];
    /**
     * @var int The y_pos to start the table headings at
     */
    private $table_heading_y_pos = 233;
    /**
     * @var bool Whether to include the to address or not
     */
    public $include_address = true;

    /**
     * Initializes the default invoice PDF
     *
     * @param string $orientation The paper orientation (optional, default 'P')
     * @param string $unit The measurement unit (optional, default 'mm')
     * @param string $format The paper format (optional, default 'A4')
     * @param bool $unicode True if the PDF should support unicode characters (optional, default true)
     * @param string $encoding The character encoding (optional, default 'UTF-8')
     * @param bool $diskcache True to cache results to disk (optional, default false)
     * @param string $font The font to use (optional, default null)
     */
    public function __construct(
        $orientation = 'P',
        $unit = 'mm',
        $format = 'A4',
        $unicode = true,
        $encoding = 'UTF-8',
        $diskcache = false,
        $font = null
    ) {
        // Invoke the parent constructor
        $format = (is_string($format) ? strtoupper($format) : $format);
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

        // Load required models
        Loader::loadModels($this, ['Invoices', 'Quotations', 'Transactions', 'TaxProviders']);

        // Default font
        $this->SetDefaultMonospacedFont('courier');
        $this->setFontInfo($font);

        $this->line_options = [
            'x_pos' => 44,
            'y_pos' => $this->table_heading_y_pos,
            'border' => 'RL',
            'height' => 22,
            'line_style' => ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'color' => self::$primary_color],
            'font' => $this->font,
            'font_size' => self::$font_size_alt,
            'padding' => self::$font_size_alt,
            'col' => [
                'name' => [
                    'width' => 314
                ],
                'qty' => [
                    'width' => 70,
                    'align' => 'C'
                ],
                'unit_price' => [
                    'width' => 70,
                    'align' => 'R'
                ],
                'price' => [
                    'width' => 70,
                    'align' => 'R',
                //'border'=>"B"
                ],
            ],
            'cell' => [['name' => ['align' => 'L']]]
        ];

        if ($this->direction == 'rtl') {
            $this->line_options['cell'][0]['name']['align'] = 'R';
            $this->line_options['col']['qty']['align'] = 'R';
        }

        $this->payment_options = [
            'x_pos' => 44,
            'y_pos' => $this->table_heading_y_pos,
            'border' => 'RL',
            'height' => 22,
            'line_style' => ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'color' => self::$primary_color],
            'font' => $this->font,
            'font_size' => self::$font_size_alt,
            'padding' => self::$font_size_alt,
            'col' => [
                'applied_date' => [
                    'width' => 131
                ],
                'type_name' => [
                    'width' => 131,
                    'align' => 'C'
                ],
                'transaction_id' => [
                    'width' => 131,
                    'align' => 'R'
                ],
                'applied_amount' => [
                    'width' => 131,
                    'align' => 'R',
                ],
            ],
            'cell' => [['applied_date' => ['align' => 'L']]]
        ];

        if ($this->direction == 'rtl') {
            $this->payment_options['cell'][0]['applied_date']['align'] = 'R';
            $this->payment_options['col']['type_name']['align'] = 'R';
        }

        // Set margins
        $this->SetMargins(44, 260, 44);
        $this->SetFooterMargin(160);

        // Set auto page breaks y-px from the bottom of the page
        $this->SetAutoPageBreak(true, 190);
    }

    /**
     * Overwrite the default header that appears on each page of the PDF
     */
    public function Header()
    {
        $this->SetTextColorArray(self::$primary_text_color);

        // Draw the background
        $this->drawBackground();

        // Draw the paid watermark
        $this->drawPaidWatermark();

        // Set logo
        $this->drawLogo();

        // Set the page mark so background images will display correctly
        $this->setPageMark();

        // Draw the return address
        $this->drawReturnAddress();

        // Place the invoice type on the document
        $this->drawInvoiceType();

        // Set Address
        if ($this->include_address) {
            $this->drawAddress();
        }

        // Add Invoice Number, Customer Number, Invoice Date
        $this->drawInvoiceInfo();

        // Draw due date
        $this->drawInvoiceSubInfo();

        // Draw the line items table heading on each page
        $this->drawLineHeader();

        // Set the position where the header finishes
        $this->header_end_y = $this->GetY();

        // Set the top margin again, incase any header methods expanded this area.
        $this->SetTopMargin($this->header_end_y);
    }

    /**
     * Overwrite the default footer that appears on each page of the PDF
     */
    public function Footer()
    {
        $this->SetTextColorArray(self::$primary_text_color);

        // Set the terms of the document
        if (!empty($this->meta['terms']) && !($this->meta['quotation'] ?? false)) {
            $this->drawTerms();
        }

        // Set the page number of the document
        $this->drawPageNumber();
    }

    /**
     * Draws a complete invoice
     */
    public function drawInvoice()
    {
        // Create a clone of the invoice in order to determine which line items
        // end each page
        $clone = clone $this;
        $clone_options = $clone->line_options;
        $clone_options['y_pos'] = max($clone->header_end_y, $clone->GetY());

        $last_lines = [];
        $page = $clone->getPage();

        if ($this->meta['quotation'] ?? false) {
            $presenter = $this->Quotations->getPresenter($this->invoice->id);
        } else {
            $presenter = $this->Invoices->getPresenter($this->invoice->id);
        }

        $i = 0;
        foreach ($presenter->items() as $item) {
            $line = [
                'name' => $item->description,
                'qty' => $this->CurrencyFormat->truncateDecimal($item->qty, 0),
                'unit_price' => $item->price,
                'price' => $clone->CurrencyFormat->format(
                    $item->total,
                    $clone->invoice->currency
                ),
            ];

            // Draw invoice line
            $clone->drawTable([$line], $clone_options);
            $current_page = $page;
            $page = $clone->getPage();
            $clone_options['y_pos'] = $clone->GetY();

            // The previous line item ended the previous page
            if ($current_page != $page) {
                // Save the line item index
                $last_lines[] = ($i - 1 < 0 ? 0 : $i - 1);
            }
            $i++;
        }

        unset($clone, $clone_options);

        // Build the actual line items
        $lines = [];
        $options = $this->line_options;
        $options['y_pos'] = max($this->header_end_y, $this->GetY());
        $j = 0;
        foreach ($presenter->items() as $item) {
            $lines[] = [
                'name' => $item->description,
                'qty' => $this->CurrencyFormat->truncateDecimal($item->qty, 0),
                'unit_price' => $this->CurrencyFormat->format(
                    $item->price,
                    $this->invoice->currency
                ),
                'price' => $this->CurrencyFormat->format(
                    $item->total,
                    $this->invoice->currency
                ),
            ];

            // Set a border on the last items on the page
            if (in_array($j, $last_lines)) {
                $options['row'][$j] = ['border' => 'BLR'];
            }
            $j++;
        }

        // Draw invoice lines
        $this->drawTable($lines, $options);

        // Draw public notes and invoice tallies
        $this->drawTallies();

        // Draw transaction payments/credits
        if (!($this->meta['quotation'] ?? false)) {
            $this->drawPayments();
        }
    }

    /**
     * Set the fonts and font attributes to be used in the document
     *
     * @param string $font The font to set
     */
    private function setFontInfo($font)
    {
        $lang = [];
        $lang['a_meta_charset'] = 'UTF-8';
        $lang['a_meta_dir'] = Language::_('AppController.lang.dir', true)
            ? Language::_('AppController.lang.dir', true)
            : 'ltr';

        // Set language settings
        $this->setLanguageArray($lang);

        if ($font) {
            $this->font = $font;
        }

        // Set font
        $this->SetFont($this->font, '', self::$font_size);

        // Set default text color
        $this->SetTextColorArray(self::$primary_text_color);
    }

    /**
     * Draws the paid text in the background of the invoice
     */
    private function drawPaidWatermark()
    {
        // Show paid watermark
        if (!empty($this->meta['display_paid_watermark'])
            && $this->meta['display_paid_watermark'] == 'true'
            && !empty($this->invoice->date_closed)
            && !($this->meta['quotation'] ?? false)
        ) {
            $max_height = $this->getPageHeight();
            $max_width = $this->getPageWidth();

            $options = [
                'x_pos' => 44, // start within margin
                'y_pos' => ($max_height - 125) / 2, // vertical center
                'font' => $this->font,
                'font_size' => 100,
                'row' => [['font_style' => 'B', 'align' => 'C']]
            ];

            $data = [
                ['col' => Language::_('QuickbooksInvoice.watermark_paid', true)]
            ];

            // Set paid background text color
            $this->SetTextColorArray([230, 230, 230]);

            // Rotate the text
            $this->StartTransform();
            // Rotate 45 degrees from midpoint
            $this->Rotate(45, ($max_width) / 2, ($max_height) / 2);

            $this->drawTable($data, $options);

            $this->StopTransform();

            // Set default text color
            $this->SetTextColorArray(self::$primary_text_color);
        }
    }

    /**
     * Renders public notes and invoice tallies onto the document
     */
    private function drawTallies()
    {
        $page = $this->getPage();

        $options = [
            'border' => 1,
            'x_pos' => 44,
            'y_pos' => max($this->header_end_y, $this->GetY()),
            'font' => $this->font,
            'font_size' => self::$font_size_alt,
            'col' => [
                [
                    'height' => 12,
                    'width' => 384
                ]
            ]
        ];

        // Parse tags
        $tags = [
            'client' => $this->invoice->client ?? (object) [],
            'company' => ['name' => $this->meta['company_name'], 'address' => $this->meta['company_address']]
        ];

        if ($this->meta['quotation'] ?? false) {
            $tags['quotation'] = $this->invoice;
            $tags['invoices'] = $this->Quotations->getInvoices($this->invoice->id);
        } else {
            $tags['invoice'] = $this->invoice;
            $tags['transactions'] = $this->Transactions->getApplied(null, $this->invoice->id);
        }

        $notes = null;
        try {
            $notes = H2o::parseString(
                $this->invoice->note_public ?? $this->invoice->notes ?? '',
                Configure::get('Blesta.parser_options')
            )->render($tags);
        } catch (Throwable $e) {
            $notes = $this->invoice->note_public ?? $this->invoice->notes ?? '';
        }

        // Draw notes
        $y_pos = 0;
        if (!empty($this->invoice->note_public) || !empty($this->invoice->notes)) {
            $note_options = $options;

            if ($this->direction == 'rtl') {
                $note_options['align'] = 'R';
            }

            $data = [
                [Language::_('QuickbooksInvoice.notes_heading', true)],
                [$notes]
            ];

            // Draw notes
            $this->drawTable($data, $note_options);
            $y_pos = $this->GetY();
        }

        $this->setPage($page);

        // Set subtitle
        $data = [
            [
                'notes' => '',
                'label' => Language::_('QuickbooksInvoice.subtotal_heading', true),
                'price' => $this->CurrencyFormat->format(
                    $this->invoice->subtotal,
                    $this->invoice->currency
                )
            ]
        ];

        // Set all taxes
        if ($this->meta['quotation'] ?? false) {
            $presenter = $this->Quotations->getPresenter($this->invoice->id);
        } else {
            $presenter = $this->Invoices->getPresenter($this->invoice->id);
        }
        foreach ($presenter->taxes() as $tax) {
            $data[] = [
                'notes' => '',
                'label' => $tax->description,
                'price' => $this->CurrencyFormat->format(
                    $tax->total,
                    $this->invoice->currency
                )
            ];
        }

        // Set total
        $data[] = [
            'notes' => '',
            'label' => Language::_('QuickbooksInvoice.total_heading', true),
            'price' => $this->CurrencyFormat->format($this->invoice->total, $this->invoice->currency)
        ];

        if (($tax_provider = $this->TaxProviders->getByCountry($this->invoice->client->country))) {
            // Set tax notes
            $notes = $tax_provider->getNotes($this->invoice);
            foreach ($notes as $note) {
                $data[] = ['notes' => $note];
            }
        }

        $options['padding'] = self::$font_size_alt;
        $options['col'] = [
            'notes' => [
                'width' => 384,
                'border' => 0
            ],
            'label' => [
                'width' => 70,
                'align' => 'R',
                'font_style' => 'B'
            ],
            'price' => [
                'width' => 70,
                'align' => 'R',
            ],
        ];
        $options['cell'] = [['notes' => ['border' => 'T']]];

        // Draw tallies
        $this->drawTable($data, $options);

        // Set the Y position to the greater of the notes area, or the subtotal/total area
        $this->SetY(max($y_pos, $this->GetY()));
    }

    /**
     * Renders a heading, typically above a table
     *
     * @param string $heading The heading to set for the table
     */
    private function drawTableHeading($heading)
    {
        $options = $this->payment_options;
        $options = [
            'font_size' => self::$font_size,
            'y_pos' => max($this->table_heading_y_pos, $this->GetY()),
            'border' => 'TRL',
            'height' => 22,
            'line_style' => ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'color' => self::$primary_color],
            'padding' => self::$font_size_alt,
            'col' => [
                'heading' => [
                    'width' => 524
                ]
            ],
            'cell' => [['heading' => ['align' => 'L']]]
        ];

        // Add space between the end of the previous table and the beginning of this heading
        // iff this heading is not at the top of the page
        if ($this->table_heading_y_pos < $this->GetY()) {
            $options['y_pos'] = $this->GetY() + 10;
        }

        if ($this->direction == 'rtl') {
            $options['cell'][0]['heading']['align'] = 'R';
        }

        $data = [
            ['heading' => $heading],
        ];

        // Draw table heading
        $this->drawTable($data, $options);
    }

    /**
     * Renders the transaction payments/credits header onto the document
     */
    private function drawPaymentHeader()
    {
        // Add a heading above the table
        $this->drawTableHeading(Language::_('QuickbooksInvoice.payments_heading', true));

        // Use the same options as line items
        $options = $this->payment_options;
        // Start header at top of page, or current page below the other table
        $options['y_pos'] = max($this->table_heading_y_pos, $this->GetY());

        // Draw the transaction payment header
        $options['row'] = [['font_size' => self::$font_size, 'border' => 1, 'align' => 'C']];

        if ($this->direction == 'rtl') {
            $options['row'][0]['align'] = 'R';
        }

        $header = [[
            'applied_date' => Language::_('QuickbooksInvoice.payments_applied_date', true),
            'type_name' => Language::_('QuickbooksInvoice.payments_type_name', true),
            'transaction_id' => Language::_('QuickbooksInvoice.payments_transaction_id', true),
            'applied_amount' => Language::_('QuickbooksInvoice.payments_applied_amount', true)
        ]];

        $this->drawTable($header, $options);
    }

    /**
     * Renders the transaction payments/credits section onto the document
     */
    private function drawPayments()
    {
        if (!empty($this->meta['display_payments']) && $this->meta['display_payments'] == 'true') {
            // Set the payment rows
            $options = $this->payment_options;
            $rows = [];
            $i = 0;
            foreach ($this->invoice->applied_transactions as $applied_transaction) {
                // Only show approved transactions
                if ($applied_transaction->status != 'approved') {
                    continue;
                }

                // Use the type name, or the gateway name
                $type_name = $applied_transaction->type_real_name;
                if ($applied_transaction->type == 'other' && $applied_transaction->gateway_type == 'nonmerchant') {
                    $type_name = $applied_transaction->gateway_name;
                }

                $rows[$i] = [
                    'applied_date' => $this->Date->cast(
                        $applied_transaction->applied_date,
                        $this->invoice->client->settings['date_format']
                    ),
                    'type_name' => $type_name,
                    'transaction_id' => $applied_transaction->transaction_number,
                    'applied_amount' => $this->CurrencyFormat->format(
                        $applied_transaction->applied_amount,
                        $applied_transaction->currency
                    )
                ];
                $i++;
            }

            // Don't draw the table if there are no payments
            if (empty($rows)) {
                return;
            }

            // Draw the table headings
            $this->drawPaymentHeader();

            // Draw the table rows
            $options['y_pos'] = max($this->table_heading_y_pos, $this->header_end_y, $this->GetY());
            $this->drawTable($rows, $options);

            // Set balance due at bottom of table
            $data = [
                [
                    'blank' => '',
                    'label' => Language::_('QuickbooksInvoice.balance_heading', true),
                    'price' => $this->CurrencyFormat->format($this->invoice->due, $this->invoice->currency)
                ]
            ];

            $options['y_pos'] = max($this->table_heading_y_pos, $this->header_end_y, $this->GetY());
            $options['row'] = ['blank' => ['border' => 0]];
            $options['padding'] = self::$font_size_alt;
            $options['col'] = [
                'blank' => [
                    'width' => 262,
                    'border' => 'T'
                ],
                'label' => [
                    'width' => 131,
                    'align' => 'R',
                    'border' => 1,
                    'font_style' => 'B'
                ],
                'price' => [
                    'width' => 131,
                    'align' => 'R',
                    'border' => 1
                ],
            ];

            // Draw balance
            $this->drawTable($data, $options);
        }
    }

    /**
     * Renders the background image onto the document
     */
    private function drawBackground()
    {
        // Set background image by fetching current margin break,
        // then disable page break, set the image, and re-enable page break with
        // the current margin
        $bMargin = $this->getBreakMargin();
        $auto_page_break = $this->getAutoPageBreak();
        $margins = $this->getMargins();

        $this->SetMargins(0, 0, 0, true);
        $this->SetAutoPageBreak(false, 0);

        if (file_exists($this->meta['background'])) {
            $this->Image(
                $this->meta['background'],
                0,
                0,
                0,
                0,
                '',
                '',
                '',
                true,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                true
            );
        }

        // Restore auto-page-break status and margins
        $this->SetAutoPageBreak($auto_page_break, $bMargin);
        $this->setPageMark();
        $this->SetMargins($margins['left'], $margins['top'], $margins['right'], true);
    }

    /**
     * Renders the logo onto the document
     */
    private function drawLogo()
    {
        // Really wish we could align right, but aligning right will not respect the
        // $x parameter in TCPDF::Image(), so we must manually set the off-set. That's ok
        // because we're setting the width anyway.
        if ($this->meta['display_logo'] == 'true' && file_exists($this->meta['logo'])) {
            if ($this->direction == 'rtl') {
                $this->Image($this->meta['logo'], 187, 35, 140);
            } else {
                $this->Image($this->meta['logo'], 432, 35, 140);
            }
        }
    }

    /**
     * Fetch the date due to display
     *
     * @return string The date due to display
     */
    protected function getDateDue()
    {
        $date_due = null;
        switch ($this->invoice->status) {
            case 'proforma':
                $due_date_option = 'display_due_date_proforma';
                break;
            case 'draft':
                $due_date_option = 'display_due_date_draft';
                break;
            default:
                $due_date_option = 'display_due_date_inv';
                break;
        }
        if ($this->meta[$due_date_option] == 'true' && !empty($this->invoice->date_due)) {
            $date_due = $this->invoice->date_due;
        }
        return $date_due;
    }

    /**
     * Renders the Invoice info section to the document, containing the invoice ID, client ID, date billed
     */
    private function drawInvoiceInfo()
    {
        // Set the invoice number label language
        $inv_id_code_lang = 'QuickbooksInvoice.invoice_id_code';
        if ($this->meta['quotation'] ?? false) {
            $inv_id_code_lang = 'QuickbooksInvoice.quotation_id_code';
        } else if (in_array($this->invoice->status, ['proforma', 'draft']) && !($this->meta['quotation'] ?? false)) {
            $inv_id_code_lang = 'QuickbooksInvoice.' . $this->invoice->status . '_id_code';
        }

        $data = [
            [
                'client_id' => Language::_('QuickbooksInvoice.client_id_code', true),
                'date' => Language::_('QuickbooksInvoice.date_billed', true),
                'invoice_id' => Language::_($inv_id_code_lang, true)
            ],
            [
                'client_id' => $this->invoice->client->id_code,
                'date' => $this->Date->cast(
                    $this->invoice->date_billed,
                    $this->invoice->client->settings['date_format']
                ),
                'invoice_id' => $this->invoice->id_code
            ]
        ];

        if (!empty($this->invoice->date_created)) {
            $data[0]['date'] = Language::_('QuickbooksInvoice.date_created', true);
            $data[1]['date'] = $this->Date->cast(
                $this->invoice->date_created,
                $this->invoice->client->settings['date_format']
            );
        }

        if (!empty($this->invoice->date_expires)) {
            $data[0]['date'] = Language::_('QuickbooksInvoice.date_expires', true);
            $data[1]['date'] = $this->Date->cast(
                $this->invoice->date_expires,
                $this->invoice->client->settings['date_format']
            );
        }

        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size,
            'padding' => self::$font_size / 2,
            'x_pos' => -294,
            'y_pos' => 120,
            'border' => 1,
            'line_style' => ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'color' => self::$primary_color],
            'align' => 'C',
            'col' => [
                'date' => ['width' => 80],
                'client_id' => ['width' => 90],
                'invoice_id' => ['width' => 80],
            ]
        ];

        if ($this->direction == 'rtl') {
            $options['align'] = 'R';
        }
        $this->drawTable($data, $options);
    }

    /**
     * Renders the Invoice sub info section to the document, containing the date due
     */
    private function drawInvoiceSubInfo()
    {
        $date_due = $this->getDateDue();
        if (!$date_due) {
            return;
        }

        $data = [
            [
                'date_due' => Language::_('QuickbooksInvoice.date_due', true)
            ],
            [
                'date_due' => $this->Date->cast(
                    $this->invoice->date_due,
                    $this->invoice->client->settings['date_format']
                )
            ]
        ];

        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size,
            'padding' => self::$font_size / 2,
            'x_pos' => -124,
            'y_pos' => 192,
            'border' => 'LR',
            'line_style' => ['width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'color' => self::$primary_color],
            'align' => 'C',
            'col' => [
                'date_due' => ['width' => 80]
            ],
            'row' => [['border' => 1, 'padding' => self::$font_size / 2]]
        ];

        if ($this->direction == 'rtl') {
            $options['align'] = 'R';
        }
        $this->drawTable($data, $options);
    }

    /**
     * Renders the line items table heading
     */
    private function drawLineHeader()
    {
        $options = $this->line_options;
        $options['row'] = [['font_size' => self::$font_size, 'border' => 1, 'align' => 'C']];

        if ($this->direction == 'rtl') {
            $options['row'][0]['align'] = 'R';
        }

        $header = [[
            'name' => Language::_('QuickbooksInvoice.lines_description', true),
            'qty' => Language::_('QuickbooksInvoice.lines_quantity', true),
            'unit_price' => Language::_('QuickbooksInvoice.lines_unit_price', true),
            'price' => Language::_('QuickbooksInvoice.lines_cost', true)
        ]];
        $this->drawTable($header, $options);
    }

    /**
     * Renders the to address information
     */
    private function drawAddress()
    {
        $data = [
            [Language::_('QuickbooksInvoice.address_heading', true)],
        ];

        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size,
            'x_pos' => 44,
            'y_pos' => 137,
            'align' => 'L',
            'border' => 'LR',
            'col' => [
                ['width' => 210]
            ],
            'row' => [['border' => 1, 'padding' => self::$font_size / 2]]
        ];

        if ($this->direction == 'rtl') {
            $options['align'] = 'R';
        }
        $this->drawTable($data, $options);

        // Draw an empty table box as a frame for the content
        $options['type'] = 'cell';
        $options['height'] = 86;
        $data = [];
        $data[] = [''];
        $this->drawTable($data, $options);

        // Draw the content
        $data = [[null]];
        $data[] = [$this->invoice->billing->first_name . ' ' . $this->invoice->billing->last_name];
        if (strlen($this->invoice->billing->company) > 0) {
            $data[] = [$this->invoice->billing->company];
        }
        $data[] = [$this->invoice->billing->address1];
        if (strlen($this->invoice->billing->address2) > 0) {
            $data[] = [$this->invoice->billing->address2];
        }
        $data[] = [
            Language::_(
                'QuickbooksInvoice.address_city_state',
                true,
                $this->invoice->billing->city,
                $this->invoice->billing->state,
                $this->invoice->billing->zip,
                $this->invoice->billing->country->alpha3
            )
        ];

        $options['y_pos'] = 143;
        $options['x_pos'] = 49;
        $options['row'][0]['border'] = null;
        unset($options['border'], $options['height']);

        $this->drawTable($data, $options);
    }

    /**
     * Renders the return address information including tax ID
     */
    private function drawReturnAddress()
    {
        if ($this->meta['display_companyinfo'] == 'false') {
            return;
        }

        $data = [
            [$this->meta['company_name']],
            [$this->meta['company_address']]
        ];

        if (isset($this->meta['tax_id']) && $this->meta['tax_id'] != '') {
            $data[] = [Language::_('QuickbooksInvoice.tax_id', true, $this->meta['tax_id'])];
        }
        if (isset($this->invoice->client->settings['tax_id']) && $this->invoice->client->settings['tax_id'] != '') {
            $data[] = [
                Language::_('QuickbooksInvoice.client_tax_id', true, $this->invoice->client->settings['tax_id'])
            ];
        }

        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size,
            'y_pos' => 38,
            'x_pos' => 44,
            'align' => 'L'
        ];

        if ($this->direction == 'rtl') {
            $options['align'] = 'R';
        }
        $this->drawTable($data, $options);
    }

    /**
     * Sets the invoice type on the document based upon the status of the invoice
     */
    private function drawInvoiceType()
    {
        if ($this->meta['quotation'] ?? false) {
            $data = [
                [Language::_('QuickbooksInvoice.type_quotation' . ($this->invoice->status == 'draft' ? '_draft' : ''), true)]
            ];
        } else {
            $data = [
                [Language::_('QuickbooksInvoice.type_' . $this->invoice->status, true)]
            ];
        }

        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size_alt3,
            'font_style' => 'B',
            'y_pos' => 92,
            'align' => 'R'
        ];
        $this->drawTable($data, $options);
    }

    /**
     * Renders the page number to the document
     */
    private function drawPageNumber()
    {
        $font = $this->getFontBuffer($this->font);
        $group_alias = ($font && isset($font['type']) && in_array($font['type'], ['TrueTypeUnicode', 'cidfont0'])
            ? '{' . $this->getPageGroupAlias() . '}'
            : $this->getPageGroupAlias()
        );

        $data = [
            [Language::_('QuickbooksInvoice.page_of', true, $this->getGroupPageNo(), $group_alias)]
        ];
        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size_alt,
            'font_style' => 'B',
            'y_pos' => -52,
            'align' => 'R'
        ];
        $this->drawTable($data, $options);
    }

    /**
     * Renders the terms of this document
     */
    private function drawTerms()
    {
        $data = [
            [Language::_('QuickbooksInvoice.terms_heading', true)],
            [$this->meta['terms']]
        ];
        $options = [
            'font' => $this->font,
            'font_size' => self::$font_size_alt,
            'border' => 0,
            'x_pos' => 48,
            'y_pos' => -119,
            'col' => [['height' => 12]],
            'row' => [['font_style' => 'B']]
        ];

        if ($this->direction == 'rtl') {
            $options['align'] = 'R';
        }
        $this->drawTable($data, $options);
    }
}
