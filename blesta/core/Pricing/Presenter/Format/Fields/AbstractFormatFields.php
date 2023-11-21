<?php
namespace Blesta\Core\Pricing\Presenter\Format\Fields;

use stdClass;

/**
 * Abstract field formatter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format.Fields
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractFormatFields implements FormatFieldsInterface
{
    /**
     * @var The path to the directory containing JSON files
     */
    private $fieldPath;

    /**
     * Init
     */
    public function __construct()
    {
        $this->fieldPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Data' . DIRECTORY_SEPARATOR;
    }

    /**
     * {@inheritdoc}
     */
    public function invoice()
    {
        return $this->fetch('invoice');
    }

    /**
     * {@inheritdoc}
     */
    public function invoiceData()
    {
        return $this->fetch('invoice_data');
    }

    /**
     * {@inheritdoc}
     */
    public function invoiceLine()
    {
        return $this->fetch('invoice_line');
    }

    /**
     * {@inheritdoc}
     */
    public function invoiceLineData()
    {
        return $this->fetch('invoice_line_data');
    }

    /**
     * {@inheritdoc}
     */
    public function service()
    {
        return $this->fetch('service');
    }

    /**
     * {@inheritdoc}
     */
    public function serviceData()
    {
        return $this->fetch('service_data');
    }

    /**
     * {@inheritdoc}
     */
    public function serviceOption()
    {
        return $this->fetch('service_option');
    }

    /**
     * {@inheritdoc}
     */
    public function servicePackage()
    {
        return $this->fetch('service_package');
    }

    /**
     * {@inheritdoc}
     */
    public function servicePricing()
    {
        return $this->fetch('service_pricing');
    }

    /**
     * {@inheritdoc}
     */
    public function package()
    {
        return $this->fetch('package');
    }

    /**
     * {@inheritdoc}
     */
    public function packageOption()
    {
        return $this->fetch('package_option');
    }

    /**
     * {@inheritdoc}
     */
    public function packagePricing()
    {
        return $this->fetch('package_pricing');
    }

    /**
     * {@inheritdoc}
     */
    public function tax()
    {
        return $this->fetch('tax');
    }

    /**
     * {@inheritdoc}
     */
    public function discount()
    {
        return $this->fetch('discount');
    }

    /**
     * Fetches the given JSON data fields from file
     *
     * @param string $filename The filename
     * @return stdClass An stdClass object representing the fields
     */
    private function fetch($filename)
    {
        // Read the file and return the object
        $path = $this->fieldPath . $filename . '.json';

        if (file_exists($path)) {
            $fp = fopen($path, 'rb');

            $data = '';
            while (($chunk = fread($fp, 8192))) {
                $data .= $chunk;
            }

            fclose($fp);

            return json_decode($data);
        }

        return new stdClass();
    }
}
