<?php
namespace Blesta\Core\Pricing\Presenter\Build\ServiceChange;

use Blesta\Core\Pricing\Presenter\Build\Options\AbstractOptions;
use Blesta\Core\Pricing\Presenter\Format\FormatFactory;
use Blesta\Core\Pricing\Presenter\Items\ServiceFactory;
use Blesta\Core\Pricing\Presenter\PresenterFactory;
use Blesta\Core\Pricing\PricingFactory;
use Blesta\Items\ItemFactory;

/**
 * Abstract service change builder
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.ServiceChange
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractServiceChangeBuilder extends AbstractOptions implements ServiceChangeBuilderInterface
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

    /**
     * Sets all discounts
     *
     * @param array $discounts A list of two arrays ('old' and 'new') each with stdClass objects
     *  representing each coupon that apply either before or after the service change, containing:
     *
     *  - id The coupon ID
     *  - code The coupon code
     *  - used_qty The number of times the coupon has been used
     *  - max_qty The max number of coupon uses
     *  - start_date The date the coupon begins
     *  - end_date The date the coupon ends
     *  - status The coupon status
     *  - type The type of coupon as it applies to packages (exclusive, inclusive)
     *  - recurring 1 or 0, whether the coupon applies to recurring services
     *  - limit_recurring 1 or 0, whether the coupon limitations apply to recurring services
     *  - apply_package_options 1 or 0, whether the coupon applies to a service's package options
     *  - amounts An array of stdClass objects representing each coupon amount, containing:
     *      - coupon_id The coupon ID
     *      - currency The coupon amount currency
     *      - amount The coupon amount
     *      - type The coupon amount type (percent, amount)
     *  - packages An array of stdClass objects representing each assigned coupon package, containing:
     *      - coupon_id The coupon ID
     *      - package_id The assigned package ID
     */
    public function discounts(array $discounts)
    {
        $this->discounts = $discounts;

        return $this;
    }
}
