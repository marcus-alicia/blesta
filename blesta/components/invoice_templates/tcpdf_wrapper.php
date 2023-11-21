<?php
/**
 * TCPDF Wrapper. Extends the TCPDF library to make it easier to use for
 * building invoices.
 *
 * @package blesta
 * @subpackage blesta.components.invoice_templates
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class TcpdfWrapper extends TCPDF
{
    /**
     * @var string The direction of the document
     */
    protected $direction = 'ltr';

    /**
     * Initializes the TCPDF Wrapper
     *
     * @param string $orientation The paper orientation (optional, default 'P')
     * @param string $unit The measurement unit (optional, default 'mm')
     * @param string $format The paper format (optional, default 'A4')
     * @param bool $unicode True if the PDF should support unicode characters (optional, default true)
     * @param string $encoding The character encoding (optional, default 'UTF-8')
     * @param bool $diskcache True to cache results to disk (optional, default false)
     */
    public function __construct($orientation, $unit, $format, $unicode, $encoding, $diskcache)
    {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

        // Set document direction
        $this->direction = Language::_('AppController.lang.dir', true)
            ? Language::_('AppController.lang.dir', true)
            : 'ltr';
    }

    /**
     * Draws a table using the given data and options
     *
     * @param array $data An array of 'column' => values
     * @param array $options An array of options affecting the table including:
     *
     *  - type The type of table (multicell or cell, default 'multicell')
     *  - x_pos The X position of the table (default current X post)
     *  - y_pos The Y position of the table (default current Y pos)
     *  - border Border thickness (default 0)
     *  - align Table alignment (default L)
     *  - text_color An RGB array of text color (default null, whatever the default text color is set to)
     *  - font_size The font size for the table (default current font size)
     *  - height The width of the cell(s) (default 0 - auto)
     *  - width The height of the cell(s) (default 0 - to end of screen)
     *  - font The font to set for the cell(s)
     *  - font_style The font style for the cell(s) 'B' = bold, 'I' = italic, 'BI' = bold and italic, 'U' = underlined
     *  - line_style The line style attributes (@see TCPDF::setLineStyle())
     *  - fill_color The color to fill the cell(s) with
     *  - padding The padding value to use for the cell(s) (null - auto padding)
     *  - col All options from $options that affect the given column by name or index
     *  - row All options from $options that affect the given row by index
     *  - cell All options from $options that affect the given cell by both column and row
     */
    protected function drawTable(array $data = [], $options = null)
    {
        // Fetch the original font-family (to be restored)
        $this->orig_font = $this->getFontFamily();

        // Fetch the original font-size (to be restored)
        $this->orig_font_size = $this->getFontSize();

        // Fetch the original font-family (to be restored)
        $this->orig_font_style = $this->getFontStyle();

        $opt = [
            'type' => 'multicell', // Accepted types: multicell, cell
            'x_pos' => $this->GetX(),
            'y_pos' => $this->GetY(),
            'border' => 0,
            'align' => 'L',
            'width' => 0,
            'height' => 0,
            'text_color' => null,
            'font' => $this->orig_font,
            'font_size' => $this->getFontSize(),
            'font_style' => $this->orig_font_style
        ];

        // Overwrite default options
        $opt = array_merge($opt, (array)$options);

        // Set the location of this table
        $this->SetXY($opt['x_pos'], $opt['y_pos']);

        // Insert rows
        for ($i = 0, $total = count($data); $i < $total; $i++) {
            // Attempt to draw the row so we can fetch the height
            $clone = clone $this;
            $page = $clone->getPage();
            $max_units = $clone->drawRow($data[$i], $i, $opt);

            // Page changed, make this row start on a new page
            if ($page !== $clone->getPage()) {
                $this->AddPage();
            }
            // Disgard what we've drawn
            unset($clone);

            // Now draw the row again, this time with all cells
            // the same height
            $prev_opt_height = isset($opt['height']) ? $opt['height'] : null;
            $opt['height'] = $max_units;

            $this->drawRow($data[$i], $i, $opt);

            if ($prev_opt_height !== null) {
                $opt['height'] = $prev_opt_height;
            } else {
                $opt['height'] = 0;
            }
        }
    }

    /**
     * Renders the table row
     *
     * @param array $row The row to render
     * @param int $i The index of this row in the table
     * @param array $opt An array of render options
     * @return int The maximum number of units required to render the height of the tallest cell
     */
    private function drawRow($row, $i, $opt)
    {
        $max_units = 0;

        // Set X position of this row
        $this->SetX($opt['x_pos']);

        if (is_array($row)) {
            $j = count($row);
            $end_row = false;

            $page_start = $this->getPage();
            $y_start = $this->GetY();
            $y_end = [];
            $page_end = [];

            // Render each column of the given row
            foreach ($row as $col => $text) {
                $lines = 0;

                // Set the cell options by merging options at various levels (table + col + row + cell)
                $cell_options = array_merge(
                    $opt,
                    (array)(isset($opt['col'][$col]) ? $opt['col'][$col] : null),
                    (array)(isset($opt['row'][$i]) ? $opt['row'][$i] : null),
                    (array)(isset($opt['cell'][$i][$col]) ? $opt['cell'][$i][$col] : null)
                );

                if (--$j == 0) {
                    $end_row = true;
                }

                // Set the cell padding if given
                if (key_exists('padding', $cell_options) && $cell_options['padding'] !== null) {
                    $this->SetCellPadding($cell_options['padding']);
                }

                // Determine line style for this cell
                if (isset($cell_options['line_style']) && is_array($cell_options['line_style'])) {
                    $this->SetLineStyle($cell_options['line_style']);
                }

                $fill = false; // transparent by default (false)
                // Set fill color, if available
                if (isset($cell_options['fill_color']) && is_array($cell_options['fill_color'])) {
                    $this->SetFillColorArray($cell_options['fill_color']);
                    $fill = true; // color given, so fill it (non-transparent)
                }

                // Set text color, if available
                $this->prev_text_color = $this->fgcolor;

                if (isset($cell_options['text_color']) && is_array($cell_options['text_color'])) {
                    $this->SetTextColorArray($cell_options['text_color']);
                }

                // Set font size specified
                if (isset($cell_options['font_size']) && is_numeric($cell_options['font_size'])) {
                    $this->SetFontSize($cell_options['font_size']);
                }

                // Determine the font for this cell
                if (isset($cell_options['font'])) {
                    if (is_array($cell_options['font'])) {
                        call_user_func_array([&$this, 'SetFont'], $cell_options['font']);
                    } else {
                        $this->SetFont(
                            $cell_options['font'],
                            isset($cell_options['font_style']) ? $cell_options['font_style'] : null
                        );
                    }
                }

                $w = max($opt['width'], $cell_options['width']);
                $h = max($opt['height'], $cell_options['height']);
                $border = (isset($cell_options['border']) ? $cell_options['border'] : null);

                $text_align = (isset($cell_options['align']) ? $cell_options['align'] : null);

                // Set page to begin drawing cell on
                $this->setPage($page_start);

                if ($cell_options['type'] == 'cell') {
                    $this->Cell($w, $h, $text, $border, ($end_row ? 1 : 0), $text_align, $fill);
                } else {
                    if ($end_row) {
                        $lines = $this->MultiCell(
                            $w,
                            $h,
                            $text,
                            $border,
                            $text_align,
                            $fill,
                            1,
                            $this->GetX(),
                            $y_start
                        );
                    } else {
                        $lines = $this->MultiCell(
                            $w,
                            $h,
                            $text,
                            $border,
                            $text_align,
                            $fill,
                            2,
                            $this->GetX(),
                            $y_start
                        );
                    }
                }

                // Calculate the height of the cell that was just drawn
                $max_units = max($max_units, $this->getLastH());

                // Record page and y position cell finished drawing on
                $page_end[] = $this->getPage();
                $y_end[] = $this->GetY();

                // Rest the font
                $this->SetFont($this->orig_font, $this->orig_font_style, $this->orig_font_size);
                // Reset the font color
                $this->SetTextColorArray($this->prev_text_color);
                // Reset cell padding
                $this->SetCellPadding(0);
            }

            $y_new = $y_end[count($y_end) - 1];

            // set the new row position by case
            if (max($page_end) == $page_start) {
                $y_new = max($y_end);
            } else {
                // If page change occurred, next row must be drawn at the last location on the last page
                $y_new = 0;
                $max_page = max($page_end);
                foreach ($page_end as $col => $page) {
                    if ($page == $max_page) {
                        $y_new = max($y_new, $y_end[$col]);
                    }
                }
            }

            $this->setPage(max($page_end));

            $this->SetY($y_new);
        }

        return $max_units;
    }
}
