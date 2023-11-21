<?php
namespace Blesta\Core\Pricing\Presenter\Format;

use Blesta\Core\Pricing\Presenter\Format\Type\Discount\DiscountFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Invoice\InvoiceFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Invoice\LineFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Option\OptionFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Options\SettingsFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Package\PackageFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Pricing\PricingFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Service\ServiceFormatter;
use Blesta\Core\Pricing\Presenter\Format\Type\Tax\TaxFormatter;
use Blesta\Core\Pricing\Presenter\Format\Fields\AbstractFormatFields;
use Blesta\Items\ItemFactory;

/**
 * Instantiates formatters
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Format
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class FormatFactory
{
    /**
     * @var An instance of FormatFieldsInterface
     */
    private $formatFields;
    /**
     * @var An instance of ItemFactory
     */
    private $itemFactory;

    /**
     * Init
     *
     * @param Blesta\Items\ItemFactory $itemFactory An instance of the ItemFactory
     * @param Blesta\Core\Pricing\Presenter\Format\Fields\AbstractFormatFields $formatFields An instance of
     *  the AbstractFormatFields
     */
    public function __construct(
        ItemFactory $itemFactory,
        AbstractFormatFields $formatFields
    ) {
        $this->itemFactory = $itemFactory;
        $this->formatFields = $formatFields;
    }

    /**
     * Retrieves a DiscountFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Discount\DiscountFormatter An instance of the DiscountFormatter
     */
    public function discount()
    {
        return new DiscountFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves an OptionFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Option\OptionFormatter An instance of the OptionFormatter
     */
    public function option()
    {
        return new OptionFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves a PackageFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Package\PackageFormatter An instance of the PackageFormatter
     */
    public function package()
    {
        return new PackageFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves a PricingFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Pricing\PricingFormatter An instance of the PricingFormatter
     */
    public function pricing()
    {
        return new PricingFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves a SettingsFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Options\SettingsFormatter An instance of the SettingsFormatter
     */
    public function settings()
    {
        return new SettingsFormatter($this->itemFactory);
    }

    /**
     * Retrieves a ServiceFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Service\ServiceFormatter An instance of the ServiceFormatter
     */
    public function service()
    {
        return new ServiceFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves an InvoiceFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Invoice\InvoiceFormatter An instance of the InvoiceFormatter
     */
    public function invoice()
    {
        return new InvoiceFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves a LineFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Invoice\LineFormatter An instance of the LineFormatter
     */
    public function invoiceLine()
    {
        return new LineFormatter($this->itemFactory, $this->formatFields);
    }

    /**
     * Retrieves a TaxFormatter
     *
     * @return Blesta\Core\Pricing\Presenter\Format\Type\Tax\TaxFormatter An instance of the TaxFormatter
     */
    public function tax()
    {
        return new TaxFormatter($this->itemFactory, $this->formatFields);
    }
}
