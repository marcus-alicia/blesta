<?php
namespace Blesta\Core\Pricing\Presenter\Build\Service;

use Blesta\Core\Pricing\Presenter\Build\Options\AbstractOptions;
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\ItemFactory;

/**
 * Abstract service item builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Service
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractServiceBuilder extends AbstractOptions implements ServiceBuilderInterface
{
    /**
     * @var Instance of FormatFactory
     */
    protected $formatFactory;
    /**
     * @var Instance of PresenterFactory
     */
    protected $presenterFactory;
    /**
     * @var Instance of PricingFactory
     */
    protected $pricingFactory;
    /**
     * @var Instance of ServiceFactory
     */
    protected $serviceFactory;
    /**
     * @var Instance of ItemFactory
     */
    protected $itemFactory;

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
        $this->pricingFactory = $pricingFactory;
        $this->presenterFactory = $presenterFactory;
        $this->itemFactory = $itemFactory;
    }
}
