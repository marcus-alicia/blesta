<?php
namespace Blesta\Core\Pricing\Modifier\Type\Proration;

use Blesta\Proration\Proration as Prorate;
use Blesta\Core\Pricing\MetaItem\Meta;
use Blesta\Core\Pricing\MetaItem\MetaItemInterface;
use Blesta\Pricing\Collection\ItemPriceCollection;
use Minphp\Date\Date;
use Configure;

/**
 * Prorates all items in an ItemPriceCollection for MetaItemPrices containing
 * relevant proration details by adjusting the MetaItemPrice unit price accordingly
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Proration
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Proration
{
    // Include the Meta trait for meta methods
    use Meta;

    /**
     * @var Minphp\Date\date An instance of the date object
     */
    private $date;

    /**
     * Init
     *
     * @param \Minphp\Date\Date An instance of the Date object
     */
    public function __construct(Date $date)
    {
        // Load Blesta configuration
        Configure::load('blesta');

        $this->date = $date;
    }

    /**
     * Prorates all items set to be prorated from MetaItemPrices in the collection
     *
     * @param Blesta\Pricing\Collection\ItemPriceCollection $collection
     * @return Blesta\Pricing\Collection\ItemPriceCollection The updated collection
     */
    public function prorate(ItemPriceCollection $collection)
    {
        foreach ($collection as $item) {
            // Skip item prices that have no meta data
            if (!($item instanceof MetaItemInterface)) {
                continue;
            }

            // Determine whether we have enough information to attempt to prorate
            $meta = $this->getMeta($item);
            if (!$this->canProrate($meta)) {
                continue;
            }

            // Get an instance of Proration to use
            $prorate = $this->getInstance(
                $meta['prorate']->startDate,
                $meta['prorate']->term,
                $meta['prorate']->period,
                (empty($meta['prorate']->prorataDay) ? null : $meta['prorate']->prorataDay),
                (empty($meta['prorate']->endDate) ? null : $meta['prorate']->endDate)
            );

            // Ensure we have the ability to prorate and that there is a prorate date to prorate to
            if (!$prorate->canProrate() && !$prorate->prorateDate()) {
                continue;
            }

            // Prorate and replace the item price
            $endDate = $prorate->prorateDate();
            $price = $item->price();

            // The price is prorated backward, so it should be a negative value
            if ($endDate && strtotime($endDate) < strtotime($meta['prorate']->startDate)) {
                $price *= -1;
            }
            $item->setPrice($prorate->proratePrice($price));

            // Update the meta information to note that this item was prorated
            // and the date it was prorated to
            $updatedMeta = [
                '_data' => ['prorated' => true, 'startDate' => $meta['prorate']->startDate, 'endDate' => $endDate],
                'prorate' => (object)['endDate' => $endDate]
            ];
            $item = $this->updateMeta($item, $updatedMeta);
        }

        return $collection;
    }

    /**
     * Determines whether there is enough information for prorating
     *
     * @param array $meta An array of meta information
     * @return bool True if the item can be prorated, or false otherwise
     */
    private function canProrate(array $meta)
    {
        // No proration set for this item
        if (empty($meta['prorate']) || !is_object($meta['prorate'])) {
            return false;
        }

        // Verify the required fields are given
        $fields = ['startDate', 'term', 'period', 'currency'];
        foreach ($fields as $field) {
            if (empty($meta['prorate']->{$field})) {
                return false;
            }
        }

        // Verify we have either the prorata day to prorate to, or have an explicit end date
        if (empty($meta['prorate']->endDate) && empty($meta['prorate']->prorataDay)) {
            return false;
        }

        // The period must be a recurring period to prorate
        if ($meta['prorate']->period == 'onetime') {
            return false;
        }

        return true;
    }

    /**
     * Creates a new proration object
     *
     * @param string $startDate The proration start date in ISO 8601 format
     * @param int $term The term
     * @param string $period The period for the term
     * @param int $prorateDay The prorata day to prorate to (optional, required if $endDate not given)
     * @param string $endDate The proration end date in ISO 8601 format (optional, required if $prorataDay not given)
     * @return Blesta\Proration\Proration
     */
    private function getInstance($startDate, $term, $period, $prorateDay = null, $endDate = null)
    {
        // Determine the prorate price
        $proration = new Prorate(
            $this->date->cast($startDate, 'c'),
            $prorateDay,
            $term,
            $period
        );
        $proration->setTimeZone(Configure::get('Blesta.company_timezone'));

        // Set an end date, if given, to override the prorate day
        if (!empty($endDate)) {
            $proration->setProrateDate($this->date->cast($endDate, 'c'));
        }

        // Allow prorating between any of the recurring periods
        $periods = [
            $proration::PERIOD_DAY,
            $proration::PERIOD_WEEK,
            $proration::PERIOD_MONTH,
            $proration::PERIOD_YEAR
        ];

        $proration->setProratablePeriods($periods);

        return $proration;
    }
}
