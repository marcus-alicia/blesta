<?php
namespace Blesta\Core\Pricing\Presenter\Build;

use Blesta\Core\Pricing\Presenter\Build\Invoice\InvoiceBuilder;
use Blesta\Core\Pricing\Presenter\Build\InvoiceData\InvoiceDataBuilder;
use Blesta\Core\Pricing\Presenter\Build\Service\ServiceBuilder;
use Blesta\Core\Pricing\Presenter\Build\ServiceData\ServiceDataBuilder;
use Blesta\Core\Pricing\Presenter\Build\ServiceChange\ServiceChangeBuilder;
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\ItemFactory;

/**
 * Instantiates builders
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BuilderFactory
{
    /**
     * @var Instance of FormatFactory
     */
    private $formatFactory;
    /**
     * @var Instance of PresenterFactory
     */
    private $presenterFactory;
    /**
     * @var Instance of PricingFactory
     */
    private $pricingFactory;
    /**
     * @var Instance of ServiceFactory
     */
    private $serviceFactory;
    /**
     * @var Instance of ItemFactory
     */
    private $itemFactory;

    /**
     * Init
     *
     * @param ServiceFactory $serviceFactory An instance of the ServiceFactory
     * @param FormatFactory $formatFactory An instance of the FormatFactory
     * @param PricingFactory $pricingFactory An instance of the PricingFactory
     * @param PresenterFactory $presenterFactory An instance of the PresenterFactory
     * @param ItemFactory $itemFactory An instance of the ItemFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FormatFactory $formatFactory,
        PricingFactory $pricingFactory,
        PresenterFactory $presenterFactory,
        ItemFactory $itemFactory
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->formatFactory = $formatFactory;
        $this->presenterFactory = $presenterFactory;
        $this->pricingFactory = $pricingFactory;
        $this->itemFactory = $itemFactory;
    }

    /**
     * Retrieves an instance of InvoiceBuilder
     *
     * @return InvoiceBuilder An instance of InvoiceBuilder
     */
    public function invoice()
    {
        return new InvoiceBuilder(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }

    /**
     * Retrieves an instance of InvoiceDataBuilder
     *
     * @return InvoiceDataBuilder An instance of InvoiceDataBuilder
     */
    public function invoiceData()
    {
        return new InvoiceDataBuilder(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }

    /**
     * Retrieves an instance of ServiceBuilder
     *
     * @return ServiceBuilder An instance of ServiceBuilder
     */
    public function service()
    {
        return new ServiceBuilder(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }

    /**
     * Retrieves an instance of ServiceDataBuilder
     *
     * @return ServiceDataBuilder An instance of ServiceDataBuilder
     */
    public function serviceData()
    {
        return new ServiceDataBuilder(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }

    /**
     * Retrieves an instance of ServiceChangeBuilder
     *
     * @return ServiceChangeBuilder An instance of ServiceChangeBuilder
     */
    public function serviceChange()
    {
        return new ServiceChangeBuilder(
            $this->serviceFactory,
            $this->formatFactory,
            $this->pricingFactory,
            $this->presenterFactory,
            $this->itemFactory
        );
    }
}
