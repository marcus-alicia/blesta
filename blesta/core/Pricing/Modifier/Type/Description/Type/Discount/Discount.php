<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type\Discount;

use Blesta\Core\Pricing\Modifier\Type\Description\Type\AbstractDescription;

/**
 * Retrieves descriptions for discount items
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type.Discount
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Discount extends AbstractDescription
{
    /**
     * {@inheritdoc}
     */
    public function get(array $meta, array $oldMeta = null)
    {
        $description = '';

        // Determine the type of meta info we have to work with
        $type = (isset($meta['_data']['type']) ? $meta['_data']['type'] : null);
        switch ($type) {
            case 'coupon':
                $description = $this->getCoupon($meta);
                break;
        }

        return $description;
    }

    /**
     * Generates a description from the discount meta data for the coupon
     *
     * @param array $meta An array of meta information
     * @return string The description
     */
    private function getCoupon(array $meta)
    {
        $fields = $this->getBaseFields($meta);

        // Include the coupon amount if it is a percentage
        $type = ($fields['type'] == 'percent' ? 'percent' : 'amount');
        // Add 0 to the amount to remove insignificant digits of 0
        $amount = ($type == 'percent' ? $fields['amount'] + 0 : '');

        return $this->_(
            'Discount.description.coupon.' . $type,
            $fields['coupon'],
            $amount
        );
    }

    /**
     * Retrieves a set of meta fields used in the descriptions
     *
     * @param array $meta An array of meta information
     * @return array An array containing the service and package name
     */
    private function getBaseFields(array $meta)
    {
        $coupon = (isset($meta['coupon']) && is_object($meta['coupon']) && isset($meta['coupon']->name))
            ? $meta['coupon']->name
            : '';
        $type = '';
        $amount = '';

        if (isset($meta['coupon_amount']) && is_object($meta['coupon_amount'])) {
            $type = isset($meta['coupon_amount']->type) ? $meta['coupon_amount']->type : '';
            $amount = isset($meta['coupon_amount']->amount) ? $meta['coupon_amount']->amount : '';
        }

        return compact('coupon', 'type', 'amount');
    }
}
