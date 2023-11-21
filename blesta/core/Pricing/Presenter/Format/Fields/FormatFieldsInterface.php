<?php
namespace Blesta\Core\Pricing\Presenter\Format\Fields;

use stdClass;

/**
 * Field formatter interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Fields
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface FormatFieldsInterface
{
    /**
     * Retrieves fields for an invoice
     *
     * @return stdClass fields
     */
    public function invoice();

    /**
     * Retrieves fields for invoice data
     *
     * @return stdClass fields
     */
    public function invoiceData();

    /**
     * Retrieves fields for an invoice line item
     *
     * @return stdClass fields
     */
    public function invoiceLine();

    /**
     * Retrieves fields for an invoice line item data
     *
     * @return stdClass fields
     */
    public function invoiceLineData();

    /**
     * Retrieves fields for a service
     *
     * @return stdClass fields
     */
    public function service();

    /**
     * Retrieves fields for service data
     *
     * @return stdClass fields
     */
    public function serviceData();

    /**
     * Retrieves fields for a service option
     *
     * @return stdClass fields
     */
    public function serviceOption();

    /**
     * Retrieves fields for a service package
     *
     * @return stdClass fields
     */
    public function servicePackage();

    /**
     * Retrieves fields for service pricing
     *
     * @return stdClass fields
     */
    public function servicePricing();

    /**
     * Retrieves fields for a package
     *
     * @return stdClass fields
     */
    public function package();

    /**
     * Retrieves fields for a package option
     *
     * @return stdClass fields
     */
    public function packageOption();

    /**
     * Retrieves fields for a package pricing
     *
     * @return stdClass fields
     */
    public function packagePricing();

    /**
     * Retrieves fields for a tax
     *
     * @return stdClass fields
     */
    public function tax();

    /**
     * Retrieves fields for a discount
     *
     * @return stdClass fields
     */
    public function discount();
}
