<?php
namespace Blesta\Core\Pricing\Presenter\Build\Options;

/**
 * Interface for options
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Presenter.Build.Options
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface OptionsInterface
{
    /**
     * Sets a list of settings
     *
     * @param array $settings An array containing all client and company settings as key/value pairs
     */
    public function settings(array $settings);

    /**
     * Sets any key/value custom options
     *
     * @param array $options An array of custom options:
     *
     *  - includeSetupFees (optional) _true/false_, whether to include applicable setup fees. Default false.
     *  - includeCancelFees (optional) _true/false_, whether to include applicable cancel fees. Default false.
     *  - applyDate* (optional) The effective _date_ the items apply. Coupons only apply with respect to
     *     this date. Default now.
     *  - startDate* (optional) The effective _date_ the service term begins. Services are dated from this
     *     startDate unless overridden by proration. Default now.
     *  - recur (optional) _true/false_, whether to treat the service as recurring, i.e., the service already
     *     exists and is renewing. May affect coupon discounts. Default false.
     *  - transfer (optional) _true/false_, whether to use the transfer price or not, i.e., the service is a domain
     *     being transferred. May affect coupon discounts. Default false.
     *  - renewal (optional) _true/false_, whether to use the renewal price or not, i.e., the service is a domain
     *     being renewed. May affect coupon discounts. Default false.
     *  - cycles (optional) The amount of term cycles the service will be renewed. Default 1
     *  - prorateStartDate* (optional) _datetime_ stamp. If set, will prorate the service from this date to
     *     the prorateEndDate
     *  - prorateEndDate* (optional) _datetime_ stamp. If set, will override the otherwise calculated prorate end date.
     *  - prorateEndDateData* (optional) _datetime_ stamp. If set, will override the otherwise set
     *     _prorateEndDate_ when included with the Service Change Presenter only. This is typically
     *     used to prorate a service from its current renew date to a new renew date by providing the
     *     _new_ renew date here while the service's current renew date is the _prorateEndDate_.
     *  - config_options (optional) _array_, a list of the config options currently on a service which
     *     is having a price change calculated.
     *  - upgrade (optional) _true/false_, whether this price is being calculated for a package upgrade.
     *  - option_currency (optional, for service changes) The ISO 4217 3-character currency code to which
     *     option prices should be converted
     *  - service_currency (optional, for service changes) The ISO 4217 3-character currency code to which
     *     service prices should be converted
     */
    public function options(array $options);

    /**
     * Sets all tax rules
     *
     * @param array $taxes An array of stdClass objects representing each tax rule that applies, containing:
     *
     *  - id The tax ID
     *  - company_id The company ID
     *  - level The tax level
     *  - name The name of the tax
     *  - amount The tax amount
     *  - type The tax type (inclusive, exclusive)
     *  - status The tax status
     */
    public function taxes(array $taxes);

    /**
     * Sets all discounts
     *
     * @param array $discounts An array of stdClass objects representing each coupon that applies, containing:
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
    public function discounts(array $discounts);
}
