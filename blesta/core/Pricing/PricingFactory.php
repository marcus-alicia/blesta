<?php
namespace Blesta\Core\Pricing;

use Blesta\Pricing\PricingFactory as PriceFactory;
use Blesta\Core\Pricing\ItemComparator\ItemComparator;
use Blesta\Core\Pricing\MetaItem\MetaItemPrice;
use Blesta\Core\Pricing\MetaItem\MetaDiscountPrice;
use Blesta\Core\Pricing\MetaItem\MetaTaxPrice;
use Blesta\Core\Pricing\Modifier\Type\Discount\Coupon;
use Blesta\Core\Pricing\Modifier\Type\Price\ArrayMerge;
use Blesta\Core\Pricing\Modifier\Type\Proration\Proration;
use Blesta\Core\Pricing\Modifier\Type\Description\Description;
use Blesta\Items\Item\ItemInterface;
use Minphp\Date\Date;
use Configure;

/**
 * Instantiates pricing objects
 *
 * @package blesta
 * @subpackage blesta.core.Pricing
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class PricingFactory extends PriceFactory
{
    /**
     * @var An array of options
     */
    private $options;

    /**
     * Init
     *
     * @param array $options An array of key/value pairs representing any custom values, including:
     *
     *  - dateFormat The date format to use
     *  - dateTimeFormat The datetime format to use
     */
    public function __construct(array $options)
    {
        $this->options = $options;

        // Load Blesta configuration
        Configure::load('blesta');
    }

    /**
     * Creates an instance of the MetaItemPrice
     *
     * @param float $amount The unit price
     * @param int $qty The quantity of unit price
     * @param string $key A unique identifier (optional, default null)
     * @return Blesta\Core\Pricing\MetaItem\MetaItemPrice
     */
    public function metaItemPrice($amount, $qty, $key = null)
    {
        return new MetaItemPrice($amount, $qty, $key);
    }

    /**
     * Creates an instance of the MetaDiscountPrice
     *
     * @throws InvalidArgumentException If $amount is negative
     *
     * @param float $amount The positive amount to discount
     * @param string $type The type of discount the $amount represents. One of:
     *
     *  - percent The $amount represents a percentage discount (NOT already divided by 100)
     *  - amount The $amount represents an amount discount
     * @return Blesta\Core\Pricing\MetaItem\MetaDiscountPrice
     */
    public function metaDiscountPrice($amount, $type)
    {
        return new MetaDiscountPrice($amount, $type);
    }

    /**
     * Creates an instance of the MetaTaxPrice
     *
     * @throws InvalidArgumentException If $amount is negative
     *
     * @param float $amount The positive tax amount as a percentage
     * @param string $type The type of tax the $rate represents. One of:
     *
     *  - inclusive_calculated Taxes are subtracted from the item price
     *  - inclusive Prices include tax
     *  - exclusive Prices do not include tax
     * @param bool $subtract Whether this tax should be subtracted instead of added
     * @return Blesta\Core\Pricing\MetaItem\MetaTaxPrice
     */
    public function metaTaxPrice($amount, $type, $subtract = false)
    {
        return new MetaTaxPrice($amount, $type, $subtract);
    }

    /**
     * Creates an instance of the ItemComparator
     *
     * @param callable $priceCallback The pricing callback that accepts four
     *  arguments for the old and new price, and the old and new ItemPrice
     *  meta data (each a Blesta\Items\Item\ItemCollection), and returns a float
     * @param callable $descriptionCallback The description callback that
     *  accepts two arguments for the old and new ItemPrice meta data (each
     *  a Blesta\Items\Item\ItemCollection or null), and returns a string
     * @return Blesta\Core\Pricing\ItemComparator\ItemComparator
     */
    public function itemComparator(callable $priceCallback, callable $descriptionCallback)
    {
        return new ItemComparator($priceCallback, $descriptionCallback);
    }

    /**
     * Creates an instance of a Coupon
     *
     * @param ItemInterface $coupon A coupon item
     * @param string $date A date
     * @return Blesta\Core\Pricing\Modifier\Type\Discount\Coupon
     */
    public function coupon(ItemInterface $coupon, $date)
    {
        return new Coupon($this->date(), $coupon, $date);
    }

    /**
     * Creates an instance of the Proration modifier
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Proration\Proration
     */
    public function proration()
    {
        return new Proration($this->date());
    }

    /**
     * Creates an instance of the Description modifier
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Description\Description
     */
    public function description()
    {
        return new Description($this->date(), $this->options);
    }

    /**
     * Creates an instance of the ArrayMerge modifier
     *
     * @return Blesta\Core\Pricing\Modifier\Type\Price\ArrayMerge
     */
    public function arrayMerge()
    {
        return new ArrayMerge();
    }

    /**
     * Creates an instance of the Minphp\Date\Date
     *
     * @return Minphp\Date\Date
     */
    public function date()
    {
        // Set the date format fields
        $formats = [];
        if (!empty($this->options['dateFormat'])) {
            $formats['date'] = $this->options['dateFormat'];
        }

        if (!empty($this->options['dateTimeFormat'])) {
            $formats['date_time'] = $this->options['dateTimeFormat'];
        }

        return new Date($formats, 'UTC', Configure::get('Blesta.company_timezone'));
    }

    /**
     * Sets any key/value custom options
     *
     * @param array $options An array of custom options:
     *
     *  - dateFormat The date format to use
     *  - dateTimeFormat The datetime format to use
     *
     * @return PricingFactory An instance of this object
     */
    public function options(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Returns an array of custom options
     *
     * @return array An array of custom options
     */
    public function getOptions()
    {
        return isset($this->options) ? $this->options : [];
    }
}
