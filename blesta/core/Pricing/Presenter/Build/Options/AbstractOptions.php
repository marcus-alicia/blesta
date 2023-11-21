<?php
namespace Blesta\Core\Pricing\Presenter\Build\Options;

use Blesta\Core\Pricing\Presenter\Format\Type\Item\ArrayFormatterInterface;
use Blesta\Core\Pricing\Presenter\Format\Type\Discount\DiscountFormatterInterface;
use Blesta\Core\Pricing\Presenter\Format\Type\Tax\TaxFormatterInterface;
use Blesta\Items\Collection\ItemCollection;

/**
 * Abstract builder for package/service option items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Options
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractOptions implements OptionsInterface
{
    /**
     * @var array All key/value settings
     */
    protected $settings = [];
    /**
     * @var array All key/value custom options
     */
    protected $options = [];
    /**
     * @var array An array of stdClass objects representing each tax rule
     */
    protected $taxes = [];
    /**
     * @var array An array of stdClass objects representing each discount
     */
    protected $discounts = [];

    /**
     * {@inheritdoc}
     *
     * @return this
     */
    public function settings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return this
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return this
     */
    public function taxes(array $taxes)
    {
        $this->taxes = $taxes;

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return this
     */
    public function discounts(array $discounts)
    {
        $this->discounts = $discounts;

        return $this;
    }

    /**
     * Formats the settings
     *
     * @param ArrayFormatterInterface $formatter The formatter
     * @return mixed The formatter's output formatting
     */
    protected function formatSettings(ArrayFormatterInterface $formatter)
    {
        return $formatter->format($this->settings);
    }

    /**
     * Formats the options
     *
     * @param ArrayFormatterInterface $formatter The formatter
     * @return mixed The formatter's output formatting
     */
    protected function formatOptions(ArrayFormatterInterface $formatter)
    {
        return $formatter->format($this->options);
    }

    /**
     * Formats the taxes
     *
     * @param TaxFormatterInterface $formatter The formatter
     * @param ItemCollection $collection The item collection to append formatted taxes to
     * @return ItemCollection A collection of Items
     */
    protected function formatTaxes(TaxFormatterInterface $formatter, ItemCollection $collection)
    {
        foreach ($this->taxes as $tax) {
            $collection->append($formatter->format((object)$tax));
        }

        return $collection;
    }

    /**
     * Formats the discounts
     *
     * @param DiscountFormatterInterface $formatter The formatter
     * @param ItemCollection $collection The item collection to append formatted discounts to
     * @return ItemCollection A collection of Items
     */
    protected function formatDiscounts(DiscountFormatterInterface $formatter, ItemCollection $collection)
    {
        foreach ($this->discounts as $discount) {
            $collection->append($formatter->format((object)$discount));
        }

        return $collection;
    }
}
