<?php
namespace Blesta\Core\Pricing\Presenter\Type;

use Blesta\Core\Pricing\Presenter\Collection\CollectionDiscounts;
use Blesta\Core\Pricing\Presenter\Collection\CollectionItems;
use Blesta\Core\Pricing\Presenter\Collection\CollectionTaxes;
use Blesta\Core\Pricing\Presenter\Collection\CollectionTotal;
use Blesta\Pricing\Collection\ItemPriceCollection;

/**
 * Abstract presenter
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Type
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractPresenter implements PresenterInterface
{
    // Include traits for building discounts, taxes, items, and totals
    use CollectionDiscounts,
        CollectionItems,
        CollectionTaxes,
        CollectionTotal;

    /**
     * @var ItemPriceCollection A collection of ItemPrices
     */
    protected $collection;

    /**
     * Init
     *
     * @param ItemPriceCollection $collection An ItemPriceCollection
     */
    public function __construct(ItemPriceCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function collection()
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function totals()
    {
        return $this->getTotals($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function discounts()
    {
        return $this->getDiscounts($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function taxes()
    {
        return $this->getTaxes($this->collection);
    }

    /**
     * {@inheritdoc}
     */
    public function items()
    {
        return $this->getItems($this->collection);
    }
}
