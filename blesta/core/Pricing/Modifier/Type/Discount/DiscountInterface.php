<?php
namespace Blesta\Core\Pricing\Modifier\Type\Discount;

/**
 * Discount interface
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Discount
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface DiscountInterface
{
    /**
     * Determines whether the coupon is active
     *
     * @return bool True if the coupon is active, or false otherwise
     */
    public function active();

    /**
     * Determines whether the coupon applies to the given packages, their options,
     * whether added new or recurring
     *
     * @param array $packageIds One of the following:
     *
     *   - An array of package IDs that the coupon must apply to
     *   - An array of package IDs mapped to periods and terms [packageID => [period => [term, term]]] that the coupon
     *      must apply to
     * @param bool $options Whether or not the coupon must apply to package options (optional, default false)
     * @param bool $recurs Whether or not the coupon is being used to recur or not (optional, default false)
     * @return bool True if the coupon applies, or false otherwise
     */
    public function applies(array $packageIds, $options = false, $recurs = false);

    /**
     * Retrieves the amount from the discount that applies for the given currency
     *
     * @param string $currency The ISO 4217 currency code
     * @return array An array of key/value pairs representing the discount amount for the given currency
     */
    public function amount($currency);

    /**
     * Retrieves the package IDs of all packages that the coupon supports
     *
     * @return array An array of package IDs
     */
    public function packages();
}
