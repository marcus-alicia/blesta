<?php
namespace Blesta\Core\Pricing\Modifier\Type\Description\Type\Tax;

use Blesta\Core\Pricing\Modifier\Type\Description\Type\AbstractDescription;

/**
 * Retrieves item descriptions for tax rules
 *
 * @package blesta
 * @subpackage blesta.core.Pricing.Modifier.Type.Description.Type.Tax
 * @copyright Copyright (c) 2019, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class Tax extends AbstractDescription
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
            case 'tax':
                $description = $this->getTax($meta);
                break;
        }

        return $description;
    }

    /**
     * Generates a description from the tax meta data
     *
     * @param array $meta An array of meta information
     * @return string The description
     */
    private function getTax(array $meta)
    {
        $fields = $this->getBaseFields($meta);

        return $this->_('Tax.description.tax', $fields['tax'], $fields['amount']);
    }

    /**
     * Retrieves a set of meta fields used in the descriptions
     *
     * @param array $meta An array of meta information
     * @return array An array containing the service and package name
     */
    private function getBaseFields(array $meta)
    {
        $tax = '';
        $amount = '';

        if (isset($meta['tax']) && is_object($meta['tax'])) {
            $tax = isset($meta['tax']->name) ? $meta['tax']->name : '';
            // Add 0 to the amount to remove insignificant digits of 0
            $amount = isset($meta['tax']->tax) ? $meta['tax']->tax + 0 : '';
        }

        return compact('tax', 'amount');
    }
}
