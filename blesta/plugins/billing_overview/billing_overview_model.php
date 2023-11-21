<?php
/**
 * BillingOverview parent model
 *
 * @package blesta
 * @subpackage blesta.plugins.billing_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class BillingOverviewModel extends AppModel
{
    public function __construct()
    {
        // Load required components/helpers
        Loader::loadComponents($this, ['Input', 'Record']);
        Loader::loadHelpers($this, ['Date', 'Javascript']);
    }
}
