<?php

namespace Blesta\Core\Util\Tax\Common;

use Blesta\Core\Util\Common\Traits\Container;
use Configure;
use Loader;

/**
 * Abstract Tax
 *
 * @package blesta
 * @subpackage blesta.core.Util.Tax.Common
 * @copyright Copyright (c) 2021, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
abstract class AbstractTax implements TaxInterface
{
    // Load traits
    use Container;

    /**
     * Fetches the name of the tax ID field
     *
     * @param stdClass $client An object representing the client to be taxed
     * @return string The name of the tax ID field
     */
    public function getTaxIdName($client = null)
    {
        return null;
    }

    /**
     * Gets a list of the states/provinces where these tax requirements apply
     *
     * @return array A list containing the state/province codes
     */
    public function getRegions()
    {
        return [];
    }

    /**
     * Gets the invoice notes from the tax provider
     *
     * @param stdClass $invoice The invoice for which to get notes
     * @return array A list of notes from the tax provider
     */
    public function getNotes($invoice)
    {
        return [];
    }
}
