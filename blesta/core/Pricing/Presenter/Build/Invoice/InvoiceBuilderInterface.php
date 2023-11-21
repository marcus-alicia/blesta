<?php
namespace Blesta\Core\Pricing\Presenter\Build\Invoice;

use stdClass;

/**
 * Invoice builder interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Invoice
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface InvoiceBuilderInterface
{
    /**
     * Builds an invoice
     *
     * @param stdClass $invoice
     */
    public function build(stdClass $invoice);
}
