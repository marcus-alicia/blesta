<?php
namespace Blesta\Core\Util\Filters\Common;

/**
 * Package Filters
 *
 * @package blesta
 * @subpackage blesta.core.Util.Filters.Common
 * @copyright Copyright (c) 2020, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
interface FiltersInterface
{
    /**
     * Gets a list of input fields for filtering packages
     *
     * @param array $options A list of options for building the filters including:
     * @param array $vars A list of submitted inputs that act as defaults for filter fields
     * @return InputFields An object representing the list of filter input field
     */
    public function getFilters(array $options, array $vars = []);
}
