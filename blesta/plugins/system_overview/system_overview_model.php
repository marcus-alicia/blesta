<?php
/**
 * SystemOverview parent model
 *
 * @package blesta
 * @subpackage blesta.plugins.system_overview
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class SystemOverviewModel extends AppModel
{
    public function __construct()
    {
        // Load required components/helpers
        Loader::loadComponents($this, ['Input', 'Record']);
        Loader::loadHelpers($this, ['Date']);
    }
}
