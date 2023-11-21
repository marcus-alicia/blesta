<?php
namespace Blesta\Core\Pricing\Presenter\Build\InvoiceData;

/**
 * Abstract invoice data item builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.InvoiceData
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface InvoiceDataBuilderInterface
{
    /**
     * Builds an invoice
     *
     * @param array $invoice
     */
    public function build(array $invoice);
}
