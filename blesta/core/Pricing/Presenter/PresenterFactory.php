<?php
namespace Blesta\Core\Pricing\Presenter;

use Blesta\Core\Pricing\Presenter\Type\InvoicePresenter;
use Blesta\Core\Pricing\Presenter\Type\InvoiceDataPresenter;
use Blesta\Core\Pricing\Presenter\Type\ServicePresenter;
use Blesta\Core\Pricing\Presenter\Type\ServiceDataPresenter;
use Blesta\Core\Pricing\Presenter\Type\ServiceChangePresenter;
use Blesta\Pricing\Collection\ItemPriceCollection;

/**
 * Instantiates presenters
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PresenterFactory
{
    /**
     * Creates a new InvoicePresenter
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection The ItemPriceCollection of items to present
     * @return Blesta\Core\Pricing\Presenter\Type\InvoicePresenter
     */
    public function invoice(ItemPriceCollection $collection)
    {
        return new InvoicePresenter($collection);
    }

    /**
     * Creates a new InvoiceDataPresenter
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection The ItemPriceCollection of items to present
     * @return Blesta\Core\Pricing\Presenter\Type\InvoiceDataPresenter
     */
    public function invoiceData(ItemPriceCollection $collection)
    {
        return new InvoiceDataPresenter($collection);
    }

    /**
     * Creates a new ServicePresenter
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection The ItemPriceCollection of items to present
     * @return Blesta\Core\Pricing\Presenter\Type\ServicePresenter
     */
    public function service(ItemPriceCollection $collection)
    {
        return new ServicePresenter($collection);
    }

    /**
     * Creates a new ServiceDataPresenter
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection The ItemPriceCollection of items to present
     * @return Blesta\Core\Pricing\Presenter\Type\ServiceDataPresenter
     */
    public function serviceData(ItemPriceCollection $collection)
    {
        return new ServiceDataPresenter($collection);
    }

    /**
     * Creates a new ServiceChangePresenter
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection The ItemPriceCollection of items to present
     * @return Blesta\Core\Pricing\Presenter\Type\ServiceChangePresenter
     */
    public function serviceChange(ItemPriceCollection $collection)
    {
        return new ServiceChangePresenter($collection);
    }
}
